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
     */
    function c_create(Console_CommandLine_Result $options){
     
        $args                           = $options->args;
        $options                        = $options->options;
        $diagnosticdata                 = new Alternc_Diagnostic_Data(Alternc_Diagnostic_Data::TYPE_ROOT);
        
        $servicelist                    = explode(',',$options["services"]);
        foreach ($servicelist as $service) {
            $class_name                 = "Alternc_Diagnostic_Service_".trim(ucfirst($service));
            if(!class_exists($class_name)){
                throw new \exception("invalid service $service");
            }
            /** @var alternc_diagnostic_service_interface */
            $serviceagent                = new $class_name( array("service" => $this) );
            
            // runs the service agent and store the results
            $diagnosticdata->addData($serviceagent->name, $serviceagent->run());
        }
        $this->formatInstance->setData($diagnosticdata)->write();
        
    }
    
    function c_diff( $options ){
    
    
        $args                           = $options->args;
        $options                        = $options->options;
	$source				= $options["source"];
	$target				= $options["target"];
	$format				= $options['format'];
	$sourceDiagnostic		= $this->getDiagnosticFromId($source);
	$targetDiagnostic		= $this->getDiagnosticFromId($target);
	$diff				= new Alternc_Diagnostic_Diff();
	$diffData			= $diff->compare($sourceDiagnostic,$targetDiagnostic);
	$formatInstance			= $this->getFormatInstance( $format );
	echo $formatInstance->dataToContent( $diffData);
    }

    function c_list( $options ){
    
        $args                           = $options->args;
        $options                        = $options->options;
	$fileList			= $this->directoryInstance->getList();
	foreach( $fileList as $number => $file ){
		echo "$number\t$file\n";
	}

    
    
    }
    function c_show( $options ){
    
    
        $args                           = $options->args;
        $options                        = $options->options;
	$id				= $options['id'];
	$format				= $options['format'];
	$data				= $this->getDiagnosticFromId( $id);
	$formatInstance			= $this->getFormatInstance( $format );
	echo $formatInstance->dataToContent( $data );

    
    }
    function c_delete( $options ){}
    
    /**
     * Finds a file by reference or name
     * 
     * @param string $file_reference
     * @return Alternc_Diagnostic_Data Resulting data
     * @todo add the ability to resolve by filename
     */
    protected function getDiagnosticFromId ( $id ) {
    
	$fileInfo			= $this->directoryInstance->getFileInfo( $id ) ;
	$extension			= $fileInfo["extension"];
	$formatInstance			= $this->getFormatInstance( $extension);
	$formatInstance->read( $fileInfo["basename"] );
	$data				= $formatInstance->getData();
	return $data;
    
    }
    
    protected function getFormatInstance ( $format ){
	switch( $format ){
	    case "json":
		    $instance		= new Alternc_Diagnostic_Format_Json( $this->directoryInstance );
		break;
	    case "txt":
		    $instance		= new Alternc_Diagnostic_Format_Txt( $this->directoryInstance );
		break;
	    default:
		throw new \Exception("Invalid format : $format");
		break;
	}
	return $instance;
    }
}
