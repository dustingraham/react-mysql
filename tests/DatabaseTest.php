<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\Database;
use React\EventLoop\Factory;

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
    
    public function testCustomLoop()
    {
        // Custom Loop
        $loop = Factory::create();
        $database = new Database($this->getCredentials(), $loop);
        $this->assertSame($loop, $database->loop);
        
        // No Custom Loop
        $databaseTwo = new Database($this->getCredentials());
        $this->assertNotSame($loop, $databaseTwo->loop);
    }
    
    public function testUpdateStatement()
    {
        $database = new Database($this->getCredentials());
        $database->statement(
            "UPDATE simple_table SET name = :name WHERE id = :id",
            [
                ':name' => 'update test',
                ':id' => 2
            ]
        )->then(function($result)
        {
            // Expect a true result.
            $this->assertTrue($result);
        })->done();
        
        $database->shuttingDown = true;
        $database->loop->run();
    }
}
