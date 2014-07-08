<?php 

/**
 * Central service which provides the glue and intelligence for all parts
 */
class Alternc_Diagnostic_Manager{
    
    /**
     * @var Alternc_Diagnost_Format_Abstract
     */
    public $formatInstance;
    
    /**
     * @var Alternc_Diagnostic_Directory
     */
    public $directoryInstance;

    /** @var string the Alternc version */
    public $version;

    /**
     * Constructor with dependancy injection
     * 
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options) {
        
        // Attempts to retrieve formatInstance
        if (isset($options["formatInstance"]) && ! is_null($options["formatInstance"])) {
            $this->formatInstance       = $options["formatInstance"];
        } else {
            throw new \Exception("Missing parameter formatInstance");
        }
        
         // Attempts to retrieve directoryInstance
        if (isset($options["directoryInstance"]) && ! is_null($options["directoryInstance"])) {
            $this->directoryInstance    = $options["directoryInstance"];
        } else {
            throw new \Exception("Missing parameter directoryInstance");
        }

        // Attempts to retrieve version 
        if (isset($options["version"]) && ! is_null($options["version"])) {
            $this->version		= $options["version"];
        } else {
            throw new \Exception("Missing parameter version");
        }
        
    }
    
    /**
     * Controls the diagnostics creation
     * 
     * @param Console_CommandLine_Result $options
     * @throws \Exception
     * @return string
     */
    function create(Console_CommandLine_Result $options){
     
        $args                           = $options->args;
        $options                        = $options->options;
        $diagnosticData                 = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_ROOT);
        
        $serviceList                    = explode(',',$options["services"]);
        foreach ($serviceList as $service) {
            $class_name                 = "Alternc_Diagnostic_Service_".trim(ucfirst($service));
            if(!class_exists($class_name)){
                throw new \Exception("Invalid service $service");
            }
            /** @var Alternc_Diagnostic_Service_Interface */
            $serviceAgent                = new $class_name( array("service" => $this) );
            
            // Runs the service agent and store the results
            $diagnosticData->addData($serviceAgent->name, $serviceAgent->run());
        }
        $file_name                      = $this->formatInstance->setData($diagnosticData)->write();
        return "Wrote diagnostic file $file_name";
    }
    
    function compare( $options ){
    
        $args                           = $options->args;
        // Attempts to retrieve file_1
        if (isset($args["file_1"])) {
            $file_1                     = $args["file_1"];
        } else {
            $file_1                     = 0;
        }
        // Attempts to retrieve file_2
        if (isset($args["file_2"])) {
            $file_2                     = $args["file_2"];
        } else {
            $file_2                     = 1;
        }
        $diffInstance                   = new Alternc_Diagnostic_Diff($this);
        
        $report                         = $diffInstance->compare($file_1,$file_2);
        
        if( null == $report){
            return new Alternc_Diagnostic_Report;
        }
        return $report;
        
    }
    /**
     * 
     * @param array $options
     * @return array
     */
    function index( $options ){
        
        return $this->directoryInstance->getListWithDates();
         
    }
    
    /**
     * 
     * @param array $options
     * @return string
     * 
     */
    function show( $options ){
        
        $args                           = $options->args;
        $options                        = $options->options;
        
        // Attempts to retrieve file_1
        if (isset($args["file"])) {
            $file_reference             = $args["file"];
        } else {
            $file_reference             = 0;
        }

        // Attempts to retrieve format
        if (isset($options["format"])) {
            $format                     = $options["format"];
        } else {
            $format                     = "json";
        }
        
        // Retrieves a resolver 
        $resolverInstance               = new Alternc_Diagnostic_Format_Resolver($this->getDirectoryInstance());
        
        // Retrieves data instance 
        $dataInstance                   = $resolverInstance->getDataInstance($file_reference);

        // Converts to string according to format requested
        if( "var_dump" == $format){
            var_dump($dataInstance);
            $return                     = "";
        }else{
            $return                     = json_encode($dataInstance);
        }
        return $return;
    }
    
    /**
     * Deletes one or more files
     * 
     * @param array $options
     * @return string
     */
    function delete( $options ){
        
        $args                           = $options->args;
        
        // Attempts to retrieve filesList
        if (isset($args["filesList"]) && count($args["filesList"])) {
            $filesList                  = $args["filesList"];
        } else {
            $filesList                  = array(0);
        }
        // Retrieves a resolver 
        $resolverInstance               = new Alternc_Diagnostic_Format_Resolver($this->getDirectoryInstance());
        $removedList                    = array();
        foreach ($filesList as $file_reference) {
            $file_name                  = $resolverInstance->resolve($file_reference);
            if( $this->directoryInstance->unlink( $file_name ) ){
                $removedList[]          = $file_name;
            }
        }
        return "Successfully removed files : ".implode(", ", $removedList)."\n";
        
    }
    
    /**
     * @param Alternc_Diagnostic_Directory directoryInstance
     */
    public function setDirectoryInstance($directoryInstance) {
        $this->directoryInstance = $directoryInstance;
        return $this;
    }

    /**
     * @return Alternc_Diagnostic_Directory
     */
    public function getDirectoryInstance() {
        return $this->directoryInstance;
    }

    /**
     * @param Alternc_Format_Abstract formatInstance
     */
    public function setFormatInstance($formatInstance) {
        $this->formatInstance = $formatInstance;
        return $this;
    }

    /**
     * @return Alternc_Format_Abstract
     */
    public function getFormatInstance() {
        return $this->formatInstance;
    }

    
}
