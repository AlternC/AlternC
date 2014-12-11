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
    function contentToData( $content ){
       
	$arrayData = json_decode( $content , true);
	$dataInstance			= new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_ROOT);
	$this->data			= $dataInstance->buildFromArray( $arrayData );
	return $this->data;

    }
    
    
    /**
     * @inherit
     */
    function dataToContent(Alternc_Diagnostic_Data $data = null ){
        
        if( $data ){
            $this->setData($data);
        }
        $content			= json_encode($this->getData());
        $filename                       = $this->getFilename();
        if(json_last_error()){
            throw new \Exception("Json conversion failed with error #".json_last_error()."for data".serialize($this->getData()));
       }
	return $content;
    }
    
    
}
