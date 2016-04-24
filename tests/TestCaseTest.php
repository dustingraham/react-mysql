<?php namespace DustinGraham\ReactMysql\Tests;

class TestCaseTest extends TestCase
{
    public function testExample()
    {
        $this->assertTrue(true);
    }
    
    public function testExtendedAssert()
    {
        foreach ([
                     [
                         'a b',
                         'a c',
                     ],
                     [
                         'alpha beta',
                         'alpha    delta',
                     ],
                     [
                         'ab',
                         'a b',
                     ],
                     [
                         ' a bc',
                         ' abc',
                     ],
                 ] as $test)
        {
            $this->assertStringNotEqualsIgnoreSpacing($test[0], $test[1]);
        }
        
        foreach ([
                     [
                         // variable internal spacing
                         'a  b',
                         'a     b',
                     ],
                     [
                         // variable spacing, longer text, more instances
                         'alpha beta  delta      gamma',
                         'alpha    beta delta   gamma',
                     ],
                     [
                         // Trailing and Leading spaces.
                         '  a  b c',
                         'a  b  c ',
                     ],
                 ] as $test)
        {
            $this->assertStringEqualsIgnoreSpacing($test[0], $test[1]);
        }
    }
}
