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


}

