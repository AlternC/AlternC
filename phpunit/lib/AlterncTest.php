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
     * @param string $fileList
     * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
     * @throws \Exception
     */
    public function loadDataSet($fileList)
    {
        if (empty($fileList)) {
            throw new \Exception("No files specified");
        }
        if( !is_array($fileList)){
            $fileList       = array($fileList);
        }
        $datasetList        = array();
        foreach ($fileList as $file_name) {
            $file               =  PHPUNIT_DATASETS_PATH."/$file_name";
            if( !is_file($file) ){
                throw new \Exception("missing $file");
            }
            $dataSet            = new PHPUnit_Extensions_Database_DataSet_YamlDataSet($file);
            $datasetList[]      = $dataSet;
        }
        $compositeDataSet            = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet($datasetList);
        return $dataSet;
    } 

    

}
