<?php
/**
 * This is the abstract class for all tests
 * @see http://phpunit.de/manual/
 */
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\DataSet\CompositeDataSet;

abstract class AlterncTest extends TestCase
{
    use TestCaseTrait;

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
            $dataSet            = new YamlDataSet($file);
            $datasetList[]      = $dataSet;
        }
        $compositeDataSet            = new CompositeDataSet($datasetList);
        return $dataSet;
    }

}
