<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\ConnectionFactory;
use DustinGraham\ReactMysql\Database;
use React\Promise\PromiseInterface;

class DatabaseTest extends TestCaseDatabase
{
    public function testCommandClass()
    {
        $database = new Database();
        
        $command = $database->createCommand();
        
        $this->assertInstanceOf(Command::class, $command);
        
        $command->bindValues([]);
    }
    
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
    
    public function testMysqliSynchronous()
    {
        $c = $this->getMysqliConnection();
        
        $result = $c->query('SELECT * FROM simple_table;');
        $this->assertEquals(2, $result->num_rows);
        
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
    
    public function testMysqliAsynchronous()
    {
        $c = $this->getMysqliConnection();
        
        $c->query('SELECT * FROM simple_table;', MYSQLI_ASYNC);
        
        $result = $c->reap_async_query();
        $this->assertEquals(2, $result->num_rows);
    }
    
    public function testCreateCommandGetPromise()
    {
        $db = new Database();
        
        $cmd = $db->createCommand();
        
        $cmd->sql = 'SELECT * FROM simple_table WHERE id = :id';
        $cmd->bind(':id', 1);
        
        $promise = $cmd->execute();
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        
        ////
        
        $promise = $db->createCommand(
            'SELECT * FROM simple_table WHERE id = :test',
            [
                ':test',
                1,
            ]
        )->execute();
        $this->assertInstanceOf(PromiseInterface::class, $promise);
    }
    
    public function testCommandResolvedResults()
    {
        $this->markTestSkipped('Still to do.');
        
        $didItWork = false;
        $db = new Database();
        $db->createCommand(
            'SELECT * FROM simple_table WHERE id = :test',
            [
                ':test',
                1,
            ]
        )->execute()->then(
            function ($results) use (&$didItWork)
            {
                // TODO
                $didItWork = count($results) > 0;
            }
        );
        
        // TODO: This probably won't work.
        $this->assertTrue($didItWork, 'It did not work.');
    }
    
    public function testAssertStrings()
    {
        $this->assertStringEqualsIgnoreSpacing('yes no', 'yes  no');
    }
    
    public function testSimpleCommandParameterBinding()
    {
        $db = new Database();
        
        $cmd = $db->createCommand();
        $cmd->sql = 'SELECT * FROM simple_table WHERE id = :id';
        $cmd->bind(':id', 1);
        
        $connection = ConnectionFactory::createConnection();
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertEquals('SELECT * FROM simple_table WHERE id = 1', $query);
    }
    
    public function testComplexCommandParameterBinding()
    {
        $this->markTestSkipped('TODO: Implement binding null values.');
        
        $db = new Database();
        
        $cmd = $db->createCommand();
        $cmd->sql = "
          INSERT INTO simple_table (
            `id`,
            `name`
          ) VALUES (
            :id,
            :name
          );
        ";
        
        $cmd->bind([
            ':id' => null,
            ':name' => 'John Cash',
        ]);
        
        $connection = ConnectionFactory::createConnection();
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertStringEqualsIgnoreSpacing(
            "INSERT INTO simple_table ( `id`, `name` ) VALUES ( NULL, 'John Cash' );",
            $query
        );
    }
}
