<?php
/**
 * This is the abstract class for all tests
 * @see http://phpunit.de/manual/
 */
abstract class AlterncTest extends PHPUnit_Extensions_Database_TestCase
{
        /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        global $database,$user,$password;
        $pdo = new PDO('mysql:dbname='.$database.';host=127.0.0.1',$user,$password);
        return $this->createDefaultDBConnection($pdo);
    }
    
    /**
     * 
     * @param type $file_name
     * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
     * @throws \Exception
     */
    public function loadDataSet($file_name)
    {
        $file               =  PHPUNIT_DATASETS_PATH."/$file_name";
        if( !is_file($file) ){
            throw new \Exception("missing $file");
        }
        $dataSet            = new PHPUnit_Extensions_Database_DataSet_YamlDataSet($file);
        return $dataSet;
    } 

    

}