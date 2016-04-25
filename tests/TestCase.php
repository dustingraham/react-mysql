<?php namespace DustinGraham\ReactMysql\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
    }
    
    public function assertStringEqualsIgnoreSpacing($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        $expected = preg_replace('/\s+/', ' ', trim($expected));
        $actual = preg_replace('/\s+/', ' ', trim($actual));
        
        $this->assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }
    
    public function assertStringNotEqualsIgnoreSpacing($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        $expected = preg_replace('/\s+/', ' ', trim($expected));
        $actual = preg_replace('/\s+/', ' ', trim($actual));
        
        $this->assertNotEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }
}
