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
	$fileList		= array();
	while($dir->valid()) {
	    if( $dir->isDot() ){
		$dir->next();
		continue;
	    }
	    $file_name		= $dir->getFilename();
	    $fileList[]		= $file_name;
	    $dir->next();
	}   
	return $fileList;
    }
 
    /**
     * @param string file_path
     */
    public function setFile_path($file_path) {
        $this->file_path                = $file_path;
        return $this;
    }


    // @todo Confirm usefulness
    public function getFileContent( $id ) {
	$fileList		= $this->getList();
	if( array_key_exists( $id, $fileList ) ){
	    return file_get_contents( $this->file_path."/".$fileList[$id]);
	}
	if( in_array($id, $fileList)){
	    $key		= array_search( $id, $fileList );
	    return file_get_contents( $this->file_path."/".$fileList[$key]);
	}
	throw new \Exception("Could not find diagnostic for id : $id");
    }

    function getFileInfo($id){

	$fileList		= $this->getList();

	if( array_key_exists( $id, $fileList ) ){
	    $file_path		= $this->file_path."/".$fileList[$id];
	}
	if( in_array($id, $fileList)){
	    $key		= array_search( $id, $fileList );
	    $file_path		= $this->file_path."/".$fileList[$key];
	}
	$fileInfos		= pathinfo( $file_path );
	return ($fileInfos);
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
