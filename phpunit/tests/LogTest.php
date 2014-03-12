<?php
class LogTest extends AlterncTest
{
    /**
     *
     * @var m_log
     */
    private $logger;


    protected function setUp()
    {
        $this->logger = new m_log();
    }
    
    protected function tearDown()
    {
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

