<?php 


/**
 * Uniform data component containing other components or real data
 */
class Alternc_Diagnostic_Data {
    
    public $index                       = array();
    public $data                        = array();
    public $type                        = "";
    public $metadata                    = null;
    
    
    const TYPE_ROOT                     = "root";
    const TYPE_DOMAIN                   = "service";
    const TYPE_SECTION                  = "section";
   
    
    public function __construct( $type, $sectionData = null) {
        $this->type                     = $type;
        if( $sectionData){
            $this->data                 = $sectionData;
        }
        
    }
    
    
    /**
     * 
     * @param array $options a module name => data
     * @return boolean
     */
    function addData( $name, Alternc_Diagnostic_Data $data){
        $this->index[]         = $name;
        $this->data[$name]    = $data;
        return true;
    }
    
    /**
     * @param array index
     */
    public function setIndex($index) {
        $this->index                    = $index;
        return $this;
    }

    /**
     * @return array
     */
    public function getIndex() {
        return $this->index;
    }
    
    /**
     * @param array data
     */
    public function setData($data) {
        $this->data                     = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

        /**
     * @param string type
     */
    public function setType($type) {
        $this->type                     = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param array metadata
     */
    public function setMetadata($metadata) {
        $this->metadata                 = $metadata;
        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata() {
        return $this->metadata;
    }

}