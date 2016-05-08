<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\Database;

class DatabaseTest extends TestCase
{
    public function testMysqliConnection()
    {
        $c = $this->getMysqliConnection();
        
        $this->assertInstanceOf(\mysqli::class, $c);
        
        $this->assertNull($c->connect_error);
        
        $this->assertEquals(0, $c->connect_errno);
        
        // Don't know if we care about these.
        // This is what the development environment was.
        // We can remove these as we understand them better.
        $this->assertEquals(10, $c->protocol_version);
        $this->assertGreaterThan(50000, $c->client_version);
        $this->assertGreaterThan(50000, $c->server_version);
        $this->assertEquals(0, $c->warning_count);
        $this->assertEquals('00000', $c->sqlstate);
    }
    
    public function testForCoverage()
    {
        new Database($this->getCredentials());
    }
}
