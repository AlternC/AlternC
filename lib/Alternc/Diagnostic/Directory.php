<?php 

class Alternc_Diagnostic_Directory {
    
    /**
     * Location of diagnostic files 
     * 
     * @var string
     */
    protected $file_path;

    public function __construct( $file_path) {
        if( null == $file_path){
            throw new \Exception("Empty file_path in Diagnostic Format handler");
        }
        if( !file_exists($file_path)){
            if( !mkdir($file_path, 0774, true)){
                throw new \Exception("Could not access path $file_path in Diagnostic Format handler");
            }
        }
        $this->file_path                = $file_path;
        
    }
    
    function getList( $max = null){
        
        $dir                    = new DirectoryIterator($this->file_path);
        
    }
 
    /**
     * @param string file_path
     */
    public function setFile_path($file_path) {
        $this->file_path                = $file_path;
        return $this;
    }

    /**
     * @return string
     */
    public function getFile_path() {
        if( null == $this->file_path){
            throw new \Exception("Missing property 'file_path' in format instance");
        }
        return $this->file_path;
    }

    
}