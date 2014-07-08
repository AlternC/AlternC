<?php


class Alternc_Diagnostic_Format_Resolver {
    

    /**
     * @var Alternc_Diagnostic_Directory
     */
    public $directoryInstance;
    
    
    /**
     * 
     * @param Alternc_Diagnostic_Directory $directoryInstance
     */
    public function __construct( Alternc_Diagnostic_Directory $directoryInstance ) {
        
        $this->directoryInstance         = $directoryInstance;
        
    }
    /**
     * Attempts to convert an int or a string to a file reference
     * 
     * @param int|string $file_reference
     * @return string
     * @throws \Exception
     * 
     */
    public function resolve( $file_reference ){
        if(is_int($file_reference) or preg_match("/\d+/",$file_reference)){
            $fileList                   = $this->directoryInstance->getList();
            if(array_key_exists($file_reference, $fileList)){
                $file_reference         = $fileList[$file_reference];
            }
            else{
                throw new \Exception("Invalid file reference $file_reference");
            }
        }
        return $file_reference;
    }


    /**
     * Finds a file by reference or name
     * 
     * @param mixed $file_reference
     * @throws \Exception
     * @return Alternc_Diagnostic_Data Resulting data
     */
    function getDataInstance( $file_reference){
        
        // Attempts to retrieve the file name
        $file_reference                 = $this->resolve($file_reference);
        // Uses factory to resolve the format
        $factoryInstance                = new Alternc_Diagnostic_Format_Factory( $file_reference, $this->directoryInstance);
        return $factoryInstance->build();
        
    }
}
