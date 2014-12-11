<?php 

/**
 * JSON implementation of the format interface : writes, reads, compares
 */
class Alternc_Diagnostic_Format_Txt
    extends Alternc_Diagnostic_Format_Abstract
    implements Alternc_Diagnostic_Format_Interface
{

    /**
     * @inherit
     */
    public function __construct(Alternc_Diagnostic_Directory $directory) {
        parent::__construct($directory);
        $this->setExtension("txt");
    }
    
    /**
     * @inherit
     */
    function contentToData( $content ){
       
	// @todo or skip ? Quite a fragile storage

    }
    
    
    /**
     * @inherit
     */
    function dataToContent(Alternc_Diagnostic_Data $data = null, $depth = 0 ){
	$d			    = $this->space_depth($depth);
	echo $d."Type: ".$data->type."\n";
	$d			    .= "  ";
	if( $data->type == Alternc_Diagnostic_Data::TYPE_SECTION ){
	    foreach( $data->data as $key => $value ){
		if( is_int( $key) ){
		    echo $d.json_encode( $value, true )."\n";
		}else{
		    echo $d.$key." => ".json_encode( $value, true )."\n";
		}
	    }
	    return;
	}
	foreach( $data->data as $section_name => $sectionData ){
	    echo $d."Section: $section_name\n";
	    $this->dataToContent( $sectionData, $depth+1);
	}
    }

    function space_depth( $depth){
	$buf			    = "";
	for( $i=0; $i < $depth; $i++){
	    $buf		    .= "    ";
	}
	return $buf;
    }
    
    
}
