<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\Connection;
use DustinGraham\ReactMysql\ConnectionFactory;
use DustinGraham\ReactMysql\Database;
use React\Promise\Promise;

class DatabaseTest extends TestCaseDatabase
{
    public function testOne()
    {
        $this->assertTrue(true);
    }
    
    public function XtestCommandClass()
    {
        $db = new Database();
        
        $command = $db->createCommand();
        
        $this->assertInstanceOf(Command::class, $command);
    }
    
    public function disabled_testMysqliConnection()
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
    
    public function disabled_testMysqliSynchronous()
    {
        $c = $this->getMysqliConnection();
        
        $result = $c->query('SELECT * FROM simple_table;');
        $this->assertEquals(3, $result->num_rows);
        
        $tempTableName = 'temptable123';
        $c->query('CREATE TEMPORARY TABLE ' . $tempTableName . ' LIKE simple_table;');
        $result = $c->query('SELECT * FROM ' . $tempTableName);
        $this->assertEquals(0, $result->num_rows);
        
        $stmt = $c->prepare('INSERT INTO ' . $tempTableName . ' (`id`, `name`) VALUES (?, ?)');
        
        $id = null;
        $name = 'john';
        
        $stmt->bind_param('is', $id, $name);
        
        $stmt->execute();
        $this->assertEquals(1, $stmt->affected_rows, 'Did not insert the row.');
        $stmt->close();
    }
    
    public function disabled_testMysqliAsynchronous()
    {
        $c = $this->getMysqliConnection();
        
        $c->query('SELECT * FROM simple_table;', MYSQLI_ASYNC);
        
        $result = $c->reap_async_query();
        $this->assertEquals(3, $result->num_rows);
    }
    
    // TODO: This test is still todo.
    public function disabled_testParamCounting()
    {
        // Note: Used a comma rather than => so it was failing.
        // param count would detect this sooner.
        
        // Intentionally bad parameters to ensure check.
        $badParams = [':test', 1,];
        // The programmer's intent was:
        // $goodParams = [ ':test' => 1, ]
        
        $db = new Database();
        $command = $db->createCommand(
            'SELECT * FROM simple_table WHERE id = :test',
            $badParams
        );
        
        $connection = ConnectionFactory::createConnection();
        $query = $command->getPreparedQuery($connection);
        
        // TODO: Here is the bad result, :test should have been 1
        // TODO: GetPreparedQuery should error on param mismatch
        $this->assertEquals(
            $query,
            'SELECT * FROM simple_table WHERE id = :test'
        );
    }
    
    public function disabled_testAssertStrings()
    {
        $this->assertStringEqualsIgnoreSpacing('yes no', 'yes  no');
    }
    
    public function disabled_testSimpleCommandParameterBinding()
    {
        $db = new Database();
        $cmd = $db->createCommand();
        $cmd->sql = 'SELECT * FROM simple_table WHERE id = :id';
        $cmd->bind(':id', 1);
        
        $connection = ConnectionFactory::createConnection();
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertEquals('SELECT * FROM simple_table WHERE id = 1', $query);
    }
    
    public function disabled_testComplexCommandParameterBinding()
    {
        $db = new Database();
        $cmd = $db->createCommand();
        $cmd->sql = "
          INSERT INTO simple_table (
            `id`,
            `name`,
            `value`,
            `created_at`
          ) VALUES (
            :id,
            :name,
            :num,
            :datetime
          );
        ";
        
        $cmd->bind([
            ':id' => null,
            ':name' => 'John Cash',
            ':num' => 7,
            ':datetime' => 'NOW()',
        ]);
        
        $connection = ConnectionFactory::createConnection();
        
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertStringEqualsIgnoreSpacing(
            "INSERT INTO simple_table ( `id`, `name`, `value`, `created_at` ) VALUES ( NULL, 'John Cash', 7, NOW() );",
            $query
        );
    }
    
    public function disabled_testPool()
    {
        $db = new Database();
        $connection = null;
        $db->getPool()->getConnection()
            ->then(function (Connection $conn) use (&$connection, $db)
            {
                $connection = $conn;
                
                // Send it back from whence it came.
                $db->getPool()->releaseConnection($conn);
            });
        
        $sameConnection = false;
        $db->getPool()->getConnection()
            ->then(function (Connection $conn) use ($connection, &$sameConnection, $db)
            {
                // Did we get the same one again?
                $sameConnection = $conn === $connection;
                
                $db->getPool()->releaseConnection($conn);
            });
        
        $this->assertTrue($sameConnection, 'Ensure it is the same exact connection.');
        
        // This will cause code coverage to cover the getConnection and releaseConnection
        // parts that deal with excess of 100 connections.
        $promises = [];
        for ($i = 0; $i < 120; $i++)
        {
            // Pool has up to 100... so pull them all!
            $promises[] = $db->getPool()->getConnection();
        }
        foreach ($promises as $promise)
        {
            $promise->then(function (Connection $conn) use ($db)
            {
                $db->getPool()->releaseConnection($conn);
            });
        }
    }
}
