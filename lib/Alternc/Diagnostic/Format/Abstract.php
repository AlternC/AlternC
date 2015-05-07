<?php 


class Alternc_Diagnostic_Format_Abstract {
    
    /**
     *
     * @var Alternc_Diagnostic_Data
     */
    public $data;

    /**
     *
     * @var Alternc_Diagnostic_Directory 
     */
    public $directory;
    
    /**
     * Files extension for the format
     * 
     * @var string
     */
    protected $extension;

    /**
     * @param string extension
     */
    public function setExtension($extension) {
        $this->extension                = $extension;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtension() {
        return $this->extension;
    }

    public function __construct(Alternc_Diagnostic_Directory $directory) {
        
        $this->directory                = $directory;

    }
    
    /**
     * @param Alternc_Diagnostic_Data  data
     */
    public function setData(Alternc_Diagnostic_Data $data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Data 
     */
    public function getData() {
        if(is_null($this->data)){
            throw new \Exception("Missing property 'data' in format instance");
        }
        return $this->data;
    }
    
    public function getFilename(){
        return $this->getDirectory()->getFile_path()."/".time().".".$this->getExtension();
    }

    /**
     * @param Alternc_Diagnostic_Directory directory
     */
    public function setDirectory($directory) {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Directory
     */
    public function getDirectory() {
        if( null == $this->directory){
            throw new \Exception("Missing property 'directory' in format instance");
        }
        return $this->directory;
    }

    
    /**
     * Writes a Data object to file
     * 
     * @return boolean 
     */
    public function write( Alternc_Diagnostic_Data $data = null  ){

	if( null == $data ){
	    if( ! $this->data ){
		throw new \Exception( "A format cannot be written without a Data");
	    }
	    $data = $this->data;
	}
	$content			= $this->dataToContent( $data );
        $filename                       = $this->getFilename();
        if( ! file_put_contents($filename, $content) ){
            throw new \Exception("Failed to write in json format to file $filename" );
        }
        return true;

    }

    /**
     * 
     * @param   string file_name
     * @return  Alternc_Diagnostic_Data A diagnostic structure 
     */
    function read( $file_name ){
       
	$content    = $this->directory->getFileContent( $file_name );
	return $this->contentToData( $content );	

    }
    
}

