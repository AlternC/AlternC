<?php 

class Alternc_Diagnostic_Directory {
    
    /**
     * Location of diagnostic files 
     * 
     * @var string
     */
    protected $file_path;

    
    /**
     * id => name (date) list
     * 
     * @var array  
     */
    protected $filesList;


    /**
     * 
     * @param string $dir_path
     * @throws \Exception
     */
    public function __construct( $dir_path) {
        if( null == $dir_path){
            throw new \Exception("Empty file_path in Diagnostic Format handler");
        }
        if( !file_exists($dir_path)){
            if( !mkdir($dir_path, 0774, true)){
                throw new \Exception("Could not crate storage directory $dir_path in Diagnostic Format handler");
            }
        }
        $this->file_path                = $dir_path;
        
    }
    
    /**
     * 
     * @param int $max
     * @return array
     */
    function getList( $max = null){
        
        if( ! is_null( $this->filesList)){
            return $this->filesList;
        }
        
        $resultArray                    = array();
        $dir                            = new DirectoryIterator($this->file_path);
        foreach( $dir as $file){
            if( $file->isDot()){
                continue;
            }
            $resultArray[]              = $file->getFilename();
        }
        $this->filesList                = $resultArray;
        return $resultArray;
    }
    /**
     * 
     * @param int $max
     * @return array
     */
    function getListWithDates( $max = null){
        
        $this->getList();
        $resultArray                    = array();
        foreach( $this->filesList as $id => $filename){
            $date                       = "?";
            if( preg_match(":(\S*)\..*:", $filename,$matches) ){
                $date                   = date("Y-m-d H:i:s", $matches[1]);
            }
            $resultArray[$id]           = "$filename ($date)";
        }
        return $resultArray;
    }
 
    public function unlink( $file_name ){
        $file_path                      = $this->file_path . DIRECTORY_SEPARATOR . $file_name;
        if( !is_file( $file_path )){
            throw new \Exception("Invalid file deletion requested : $file_path does not exist.");
        }
        if( !unlink( $file_path )){
            throw new \Exception("Failed to delete: $file_path.");
        }
        return true;
        
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