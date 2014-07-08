<?php 


class Alternc_Diagnostic_Diff{

    /** @var Alternc_Diagnostic_Manager */
    public $manager;

    public function __construct(Alternc_Diagnostic_Manager $manager) {
        $this->manager                  = $manager;
    }
    
    /**
     * 
     * @param mixed $file_1
     *          Either a number or a string refering to the file
     *          Default = Last file
     * @param mixed $file_2
     *          Either a number or a string refering to the file
     *          Default = pre-last file
     * @return Alternc_Diagnostic_Report
     */
    function compare($file_1, $file_2){

        // Instanciates a resolver able to convert int/string to Data instance
        $resolverInstance               = new Alternc_Diagnostic_Format_Resolver($this->manager->getDirectoryInstance());
        // Builds instance #1
        $data1Instance                  = $resolverInstance->getDataInstance($file_1);
        // Builds instance #2
        $data2Instance                  = $resolverInstance->getDataInstance($file_2);
        // Atttemps to compare 2 instances
        return $this->getDiff($data1Instance,$data2Instance);
    }
    

    
    
    /**
     * Compares recursively two Data objects
     * 
     * @param Alternc_Diagnostic_Data $data1Instance
     * @param Alternc_Diagnostic_Data $data2Instance
     */
    function getDiff( Alternc_Diagnostic_Data $data1Instance, Alternc_Diagnostic_Data $data2Instance ){
    
        $reportInstance                 = new Alternc_Diagnostic_Report();
        $data1Data                      = $data1Instance->getData();
        $data2Data                      = $data2Instance->getData();
        
        // Searches non present data keys
        $arrayDiff1                     = array_diff_key($data1Data, $data2Data);
        $arrayDiff2                     = array_diff_key($data2Data, $data1Data);
        
        if( count($arrayDiff1)){
            $reportInstance->missingList         = array_keys($arrayDiff1);
        }
        if(count($arrayDiff2)){
            $reportInstance->addedList         = array_keys($arrayDiff2);            
        }
        
        // Compares present data kays
        $arrayIntersect                 = array_intersect_key($data1Data, $data2Data);
        $searchKeys                     = array_keys($arrayIntersect);
        foreach ($searchKeys as $key ){
            // Compares objects
            if(is_object($data2Data[$key])){
                $result                 = $this->getDiff($data1Data[$key], $data2Data[$key]);
                if( $result ){
                    $reportInstance->modifiedData[$key]   = $result;
                }
            }
            // Compares arrays
            else if(is_array($data2Data[$key])) {
                $result                 = $this->compareArrays($data1Data[$key], $data2Data[$key]);
                if( $result ){
                    $reportInstance->modifiedData[$key]   = $result;
                }
            }else{
                if ( $data1Data[$key] != $data2Data[$key]){
                    $reportInstance->modifiedData[$key]   = array($data1Data[$key] != $data2Data[$key]);
                    
                }
            }
        }
        
        // Returns null if no difference
        if( ( ! $reportInstance->addedList ) && ( ! $reportInstance->missingList ) && ( ! $reportInstance->modifiedData ) ) {
            return null;
        }
        
        // Returns a Report object
        return $reportInstance;
    }
    
    function compareArrays( $array1, $array2){
        
        $reportInstance                 = new Alternc_Diagnostic_Report();
        natcasesort($array1);
        natcasesort($array2);
        
        // Searches non present data keys
        $arrayDiff1                     = array_diff_key($array1, $array2);
        $arrayDiff2                     = array_diff_key($array2, $array1);
        
        if( count($arrayDiff1)){
            $reportInstance->missingList = array_keys($arrayDiff1);
        }
        if(count($arrayDiff2)){
            $reportInstance->addedList  = array_keys($arrayDiff2);            
        }
        
        // Compares present data kays
        $arrayIntersect                 = array_intersect_key($array1, $array2);
        if( count($arrayIntersect)){
            $searchKeys                 = array_keys($arrayIntersect);
            $diffArray                  = array();
            foreach ($searchKeys as $key) {
                if ($array1[$key] != $array2[$key]) {
                    if( !array_search($array1[$key], $array2)){
                        $diffArray[$key] = array($array1[$key], $array2[$key]);
                    }
                }
                if (count($diffArray)) {
                    $reportInstance->modifiedData         = $diffArray;
                }
            }
        }
        // Returns null if no difference
       if( ( ! $reportInstance->addedList ) && ( ! $reportInstance->missingList ) && ( ! $reportInstance->modifiedData ) ) {
            return null;
        }
        
        // Returns a Report object
        return $reportInstance;
    }
}
