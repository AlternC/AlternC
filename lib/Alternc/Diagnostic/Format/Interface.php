<?php

interface Alternc_Diagnostic_Format_Interface{
    
    /**
     * 
     * @param   mixed $file_reference
     *          Either a number or a string refering to the file
     * @return  Alternc_Diagnostic_Data A diagnostic file
     */
    function read( $file_reference );


    /**
     * Writes a Data object to file
     * 
     * @return string file_name
     */
    function write();
    
}