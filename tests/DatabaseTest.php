<?php namespace DustinGraham\ReactMysql\Tests;

class DatabaseTest extends TestCase
{
    public $tableName = '`react`.`react`';
    
    public function testCommandClass()
    {
        $database = new \DustinGraham\ReactMysql\Database();
        
        $command = $database->createCommand();
        
        $this->assertInstanceOf(\DustinGraham\ReactMysql\Command::class, $command);
        
        $command->bindValues([]);
    }
    
    public function testMysqliConnection()
    {
        $c = \mysqli_connect('localhost', 'react', 'react', 'react');
        
        $this->assertInstanceOf(\mysqli::class, $c);
        
        $this->assertNull($c->connect_error);
        
        $this->assertEquals(0, $c->connect_errno);
        
        // Don't know if we care about these.
        // This is what the development environment was.
        // We can remove these as we understand them better.
        $this->assertEquals(10, $c->protocol_version);
        $this->assertEquals(50011, $c->client_version);
        $this->assertEquals(50505, $c->server_version);
        $this->assertEquals(0, $c->warning_count);
        $this->assertEquals('00000', $c->sqlstate);
        
        $c->close();
    }
    
    public function testMysqliSynchronous()
    {
        $c = \mysqli_connect('localhost', 'react', 'react', 'react');
        
        $result = $c->query('SELECT * FROM ' . $this->tableName);
        $this->assertEquals(3, $result->num_rows);
        
        $tempTableName = 'temptable123';
        $c->query('CREATE TEMPORARY TABLE ' . $tempTableName . ' LIKE ' . $this->tableName);
        $result = $c->query('SELECT * FROM ' . $tempTableName);
        $this->assertEquals(0, $result->num_rows);
        
        $stmt = $c->prepare('INSERT INTO ' . $tempTableName . ' (`id`, `created_by`, `created_for`, `created_at`) VALUES (?, ?, ?, ?)');
        
        $stmt->bind_param('isid', $id, $created_by, $created_for, $created_at);
        
        $id = null;
        $created_by = 'john';
        $created_for = 3;
        $created_at = 'NOW()';
        
        $stmt->execute();
        $this->assertEquals(1, $stmt->affected_rows, 'Did not insert the row.');
        $stmt->close();
        
        $c->close();
    }
    
    public function testMysqliAsynchronous()
    {
        $c = \mysqli_connect('localhost', 'react', 'react', 'react');
        
        $c->query('SELECT * FROM ' . $this->tableName, MYSQLI_ASYNC);
        
        $result = $c->reap_async_query();
        $this->assertEquals(3, $result->num_rows);
        
        $c->close();
    }
    
    public function testCreateCommandGetPromise()
    {
        $db = new \DustinGraham\ReactMysql\Database();
        
        $cmd = $db->createCommand();
        $cmd->sql = 'SELECT * FROM ' . $this->tableName . ' WHERE id = :id';
        $cmd->bind(':id', 1);
        
        $promise = $cmd->execute();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promise);
        
        ////
        
        $promise = $db->createCommand(
            'SELECT * FROM ' . $this->tableName . ' WHERE id = :test',
            [':test', 1]
        )->execute();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promise);
    }
    
    public function testCommandResolvedResults()
    {
        $this->markTestSkipped('Still to do.');
        
        $didItWork = false;
        $db = new \DustinGraham\ReactMysql\Database();
        $db->createCommand(
            'SELECT * FROM ' . $this->tableName . ' WHERE id = :test',
            [':test', 1]
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
        $db = new \DustinGraham\ReactMysql\Database();
        
        $cmd = $db->createCommand();
        $cmd->sql = 'SELECT * FROM ' . $this->tableName . ' WHERE id = :id';
        $cmd->bind(':id', 1);
        
        $connection = \DustinGraham\ReactMysql\ConnectionFactory::createConnection();
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertEquals('SELECT * FROM ' . $this->tableName . ' WHERE id = 1', $query);
    }
    
    public function testComplexCommandParameterBinding()
    {
        $this->markTestSkipped('TODO: Implement complex binding.');
        
        $db = new \DustinGraham\ReactMysql\Database();
        
        $cmd = $db->createCommand();
        $cmd->sql = "
          INSERT INTO {$this->tableName} (
            `id`,
            `created_by`,
            `created_for`,
            `created_at`
          ) VALUES (
            :id,
            :created_by,
            :created_for,
            :created_at
          );
        ";
        
        $cmd->bind([
            ':id' => null,
            ':created_by' => 'neth',
            ':created_for' => 3,
            ':created_at' => 'NOW()',
        ]);
        
        $connection = \DustinGraham\ReactMysql\ConnectionFactory::createConnection();
        $query = $cmd->getPreparedQuery($connection);
        
        $this->assertStringEqualsIgnoreSpacing(
            "INSERT INTO `react`.`react` ( `id`, `created_by`, `created_for`, `created_at` ) VALUES ( NULL, 'test', '3', NOW() );",
            $query
        );
    }
}
