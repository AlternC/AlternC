<?php

/**
 * Used to unserialize files
 */
class Alternc_Diagnostic_Format_Factory{
    
    /**
     *
     * @var string
     */
    protected $file_name;
    /**
     *
     * @var Alternc_Diagnostic_Directory
     */
    protected $directory;

    /**
     * 
     * @param string $file_name
     * @param Alternc_Diagnostic_Directory $directory
     */
    public function __construct( $file_name, Alternc_Diagnostic_Directory $directory ) {
        
        $this->file_name                    = $file_name;
        $this->directory                    = $directory;
        
    }
    
    /**
     * Reads file and converts into data
     * 
     * @return Alternc_Diagnostic_Data
     */
    function build(){
        
        $extension                          = pathinfo($this->file_name, PATHINFO_EXTENSION);
        $class_name                         = "Alternc_Diagnostic_Format_".ucfirst(strtolower($extension));
        $format                             = new $class_name($this->directory);
        $dataInstance                       = $format->read($this->file_name);
        return $dataInstance;

    }
    
    
}