<?php 

/**
 * 
 */
abstract class Alternc_Diagnostic_Service_Abstract{
    
    /** @var Alternc_Diagnostic_Data*/
    protected $data;
    
    /** @var DB_Sql*/
    public $db;

    /** @var m_mysql */
    protected $mysql;

    /** @var m_mem */
    protected $mem;

    /** @var m_admin */
    protected $admin;

    /** @var m_authip */
    protected $authip;
    /** @var m_cron */
    protected $cron;

    /** @var m_dom */
    protected $dom;

    /** @var m_ftp */
    protected $ftp;

    /** @var m_hta */
    protected $hta;

    /** @var m_mail */
    protected $mail;

    /** @var m_quota */
    protected $quota;

    public function __construct() {
        $this->data                     = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_DOMAIN);
        
        global $db;
        $this->db                       = $db;
        
        global $mem;
        $this->mem                      = $mem;
        
        global $mysql;
        $this->mysql                    = $mysql;
        
        global $quota;
        $this->quota= $quota;

        global $mail;
        $this->mail= $mail;

        global $hta;
        $this->hta= $hta;

        global $ftp;
        $this->ftp= $ftp;

        global $dom;
        $this->dom= $dom;

        global $cron;
        $this->cron= $cron;

        global $authip;
        $this->authip= $authip;

        global $admin;
        $this->admin= $admin;

    }
    
    /**
     * 
     * @param string $cmd
     * @return array
     * @throws \Exception
     */
    protected function execCmd( $cmd ){
        exec(escapeshellcmd("$cmd")." 2>&1", $output, $return_var);
        if( 0 != $return_var ){
            throw new \Exception("Invalid return for command $cmd returned error code #$return_var with output :".  json_encode($output));
        }
        return $output;
    }

    /**
     * Filters lines of a result to only include the matching lines
     * 
     * @param string $pattern
     * @param array $result
     * @return type
     */
    protected function filterRegexp($result,$pattern){
        $returnArray                    = array();
        foreach ($result as $line) {
            $captures_count             = preg_match($pattern, $line, $matches);
            if($captures_count){
                array_shift($matches);
                $returnArray[]          = implode(" ", $matches);
            }
        }
        return $returnArray;
    }


    /**
     * @param Alternc_Diagnostic_Data data
     */
    public function setData($data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Data
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Utility for filling the service agent data holder
     * 
     * @param string $name
     * @param mixed $content
     * @return boolean
     */
    function writeSectionData( $name, $content){
        
        $section                        = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_SECTION,$content);
        $this->data->addData($name, $section);
        return true;
        
    }

    /**
     * Reads an array of URL and returns the CURL results
     * 
     * @param array $urlList
     * @param array $fieldsList curlInfo array keys
     * @param int $sockets_max
     * @return array
     */
    function curlRequest($urlList,$fieldsList = array("http_code","url"),$sockets_max = 8){
        $returnArray                    = array();
        
        // Attempts to retrive a multi connection curl handle
        $multiCurlHandle                = curl_multi_init();
        for ($index = 0; $index < $sockets_max; $index++) {
            $ch                         = "ch".$index;
            $$ch                        = curl_init();
            curl_setopt($$ch, CURLOPT_HEADER,         1);
            curl_setopt($$ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($$ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($$ch, CURLOPT_TIMEOUT,        3);
            curl_setopt($$ch, CURLOPT_NOBODY,         1);
            curl_multi_add_handle($multiCurlHandle,$$ch);
        }
        
        $url_count                      = count($urlList);
        $url_pointer                    = 0;

        while( $url_pointer < $url_count){
            $sockets                    = $url_count - $url_pointer > $sockets_max ? $sockets_max : $url_count - $url_pointer ;
            $loopUrlList                = array();
            for ($index2 = 0; $index2 < $sockets; $index2++) {
                $ch                     = "ch".$index2;
                $url                    = $urlList[$url_pointer];
                $loopUrlList[$index2]   = $url;
                curl_setopt($$ch, CURLOPT_URL, $url);
                $url_pointer++;
            }
            
            do {
                curl_multi_exec($multiCurlHandle, $running);
                curl_multi_select($multiCurlHandle);
            } while ($running > 0);
            
            for ($index3 = 0; $index3 < $sockets; $index3++) {
                $ch                     = "ch".$index3;
                $url                    = $loopUrlList[$index3];
                $curlInfo               = curl_getinfo($$ch);
                $urlInfo                = array();
                foreach ($fieldsList as $field) {
                    $urlInfo[$field]    = $curlInfo[$field];
                }
                $returnArray[]          = $urlInfo;
            }
            
        }
        
        //close the handles
        curl_multi_close($multiCurlHandle);
        for ($index = 0; $index < $sockets_max; $index++) {
            $ch                         = "ch".$index;
            curl_close($$ch);
        }
        
        return $returnArray;
        
    }
    
}