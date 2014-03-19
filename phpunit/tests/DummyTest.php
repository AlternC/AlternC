<?php
/**
 * This is a fake test for the purpose of showing how tests work and eventually 
 * validate the test environment works
 * 
 * The following methods are available :
 * assertArrayHasKey()
 * assertClassHasAttribute()
 * assertClassHasStaticAttribute()
 * assertContains()
 * assertContainsOnly()
 * assertContainsOnlyInstancesOf()
 * assertCount()
 * assertEmpty()
 * assertEqualXMLStructure()
 * assertEquals()
 * assertFalse()
 * assertFileEquals()
 * assertFileExists()
 * assertGreaterThan()
 * assertGreaterThanOrEqual()
 * assertInstanceOf()
 * assertInternalType()
 * assertJsonFileEqualsJsonFile()
 * assertJsonStringEqualsJsonFile()
 * assertJsonStringEqualsJsonString()
 * assertLessThan()
 * assertLessThanOrEqual()
 * assertNull()
 * assertObjectHasAttribute()
 * assertRegExp()
 * assertStringMatchesFormat()
 * assertStringMatchesFormatFile()
 * assertSame()
 * assertSelectCount()
 * assertSelectEquals()
 * assertSelectRegExp()
 * assertStringEndsWith()
 * assertStringEqualsFile()
 * assertStringStartsWith()
 * assertTag()
 * assertThat()
 * assertTrue()
 * assertXmlFileEqualsXmlFile()
 * assertXmlStringEqualsXmlFile()
 * assertXmlStringEqualsXmlString()
 */
class DummyTest extends AlterncTest
{
    /**
     * The setup is automatically run before each test
     */
    protected function setUp()
    {
    }
    
    /**
     * The tearDown is automatically run after each test
     */
    protected function tearDown()
    {
    }
    
    
    
    /**
     * This function will NOT be executed as its name doesn't start with test*
     */
    protected function notTested()
    {
        
    }
    
    /**
     * This function will be executed by methods
     * @return boolean
     */
    public function testDependance()
    {
         $this->assertTrue(TRUE);
         return TRUE;
    }
    
    /**
     * @depends testDependance
     * @param bool $dependancyStatus Received from dependance return
     */
    public function testHasDependancy( $dependancyStatus)
    {
         $this->assertTrue($dependancyStatus);
    }


    public function testPushAndPop()
    {
        $stack = array();
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}

