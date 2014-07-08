<?php 

/**
 * JSON implementation of the format interface : writes, reads, compares
 */
class Alternc_Diagnostic_Format_Json 
    extends Alternc_Diagnostic_Format_Abstract
    implements Alternc_Diagnostic_Format_Interface
{

    /**
     * @inherit
     */
    public function __construct(Alternc_Diagnostic_Directory $directory) {
        parent::__construct($directory);
        $this->setExtension("json");
    }
    
    /**
     * @inherit
     */
    function read( $file_reference ){
        
    }
    
    
    /**
     * @inherit
     */
    function write(Alternc_Diagnostic_Data $data = null ){
        
        if( $data ){
            $this->setData($data);
        }
        $file_content                   = json_encode($this->getData());
        $filename                       = $this->getFilename();
        if(json_last_error()){
            throw new \Exception("Json conversion failed with error #".json_last_error()."for data".serialize($this->getData()));
       }
        if( ! file_put_contents($filename, $file_content) ){
            throw new \Exception("Failed to write in json format to file $filename for data".serialize($this->getData()));
        }
        return true;
    }
    
    
}