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
        
        // Attempts to check file is ok
        $this->checkIsFileReadable($file_reference);
        
        // Attempts to retrieve file content
        $file_content                   = $this->getFileContent($file_reference);
        
        // Attempts to convert string to json
        $arrayData                      = json_decode($file_content,true);
        
        // Exits if error
        if(json_last_error()){
            throw new \Exception("Failed to convert file $file_reference from JSON with PHP JSON_ERROR #". json_last_error());
        }
        
        // Returns data object
        return $this->convertJsonToData( $arrayData );
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
        return $filename;
    }
    
    
    /**
     * Operates the conversion recursively
     * 
     * @param array $arrayData
     * @return \Alternc_Diagnostic_Data
     */
    function convertJsonToData( $arrayData ){
        
        $dataInstance                   = new Alternc_Diagnostic_Data($arrayData["type"]);
        $dataInstance->setMetadata($arrayData["metadata"]);
        if( Alternc_Diagnostic_Data::TYPE_SECTION === $arrayData["type"] ){
            $dataInstance->setData($arrayData["data"]);
            return $dataInstance;
        }
        foreach($arrayData["data"] as $key => $value){
            $dataInstance->addData($key, $this->convertJsonToData($value));
        }
        return $dataInstance;
    }
    
}