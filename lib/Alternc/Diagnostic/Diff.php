<?php
error_reporting( E_ERROR );


class Alternc_Diagnostic_Diff{


    /**
     * 
     */
    function compare(Alternc_Diagnostic_Data $source, Alternc_Diagnostic_Data $target ){

	$sourceIndex		= $source->getIndex();
	$targetIndex		= $target->getIndex();
	// Check diagnostics are same level
	$source_type		= $source->getType();
	$target_type		= $target->getType();
	if( $source_type != $target_type){
	    throw new \Exception("Invalid type comparison requested: $source_type vs $target_type"); 
	}
	$diffInstance		= new Alternc_Diagnostic_Data( $source_type );
	
#echo "type $source_type\n";	
	// Compare general data
	if( $source->getMetadata() != $target->getMetadata() ){
	    $diffInstance->setMetadata( array_diff(  $source->getMetadata(), $target->getMetadata() ) );
	}

	if( $source->getIndex() != $target->getIndex() ){
	    $diffInstance->setIndex( array_diff(  $source->getIndex(), $target->getIndex() ) );
	}
	
	// If section content ie. no subsections
	if( $source_type == Alternc_Diagnostic_Data::TYPE_SECTION ){
#echo "Real section\n";	
	    if( is_array( $source->getData() ) && is_array( $target->getData() ) ){

		$diff			    = array_diff(  $source->getData(), $target->getData() ) ;
		if( $diff ){
		    $diffInstance->setData( array_diff(  $source->getData(), $target->getData()) );
		}
	    }else{
		if( $source->getData() != $target->getData() ){
		    $diffInstance->setData( array("source" =>  $source->getData(),"target" => $target->getData() ) );
		}
	    }
	}else{

	    $sourceData			    = $source->getData();
	    $targetData			    = $target->getData();
	    $seenSections		    = array();
	    foreach( $sourceData as $section_name => $sectionData ){

#echo "section_name $section_name\n";	
		$section_data_type	    = $sectionData->getType();
		if( ! isset( $targetData[$section_name] ) ) {
#echo "section_name not in target\n";	
		    $tempDataInstance	    = new Alternc_Diagnostic_Data($section_data_type);
		    $tempDataInstance->setMetadata( array("Not in target") );
		}else{
#echo "section_name for diff\n";	
		    $tempDataInstance	    = $this->compare($sectionData, $targetData[$section_name] );
		}
		if( ! is_null( $tempDataInstance ) ){
		    $diffInstance->addData( $section_name, $tempDataInstance);
		}


	    }

	}
	if( count( $diffInstance->getData() ) 
	 || count( $diffInstance->getIndex() ) 
	 || count( $diffInstance->getMetadata() )
	){
	    return $diffInstance;
	}

	return null;

    }
    
}
