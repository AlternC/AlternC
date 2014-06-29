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
     * @var Alternc_Diagnost_Directory
     */
    public $directoryInstance;

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
        
    }
    
    /**
     * Controls the diagnostics creation
     * 
     * @param Console_CommandLine_Result $options
     * @throws \Exception
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
            $serviceAgent                = new $class_name;
            
            // Runs the service agent and store the results
            $diagnosticData->addData($serviceAgent->name, $serviceAgent->run());
        }
        $this->formatInstance->setData($diagnosticData)->write();
        
    }
    
    function compare( $options ){}
    function index( $options ){}
    function show( $options ){}
    function delete( $options ){}
    
    
}
