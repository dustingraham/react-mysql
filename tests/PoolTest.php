<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\ConnectionPool;

class PoolTest extends TestCase
{
    public function testPool()
    {
        $db = $this->getDatabase();
        
        // Spin up 120 queries.
        for ($i = 0; $i < 120; $i++)
        {
            $sql = 'SELECT * FROM simple_table WHERE id = ' . $i;
            $db->statement($sql)->then(function (\mysqli_result $result)
            {
                $result->free();
            })->done();
        }
        
        $db->shuttingDown = true;
        $db->loop->run();
    }
    
    public function testSameConnectionIds()
    {
        $pool = new ConnectionPool();
        
        $id = null;
        $pool->withConnection(function ($connection) use ($pool, &$id)
        {
            $id = $connection->id;
            $pool->releaseConnection($connection);
        });
        
        $pool->withConnection(function ($connection) use ($pool, &$id)
        {
            $this->assertEquals($id, $connection->id);
            
            $pool->releaseConnection($connection);
        });
    }
    
    public function testIdenticalConnections()
    {
        $pool = new ConnectionPool();
        
        $connection = null;
        
        $pool->withConnection(function ($conn) use ($pool, &$connection)
        {
            $connection = $conn;
            $pool->releaseConnection($conn);
        });
        
        $pool->withConnection(function ($conn) use ($pool, $connection)
        {
            $this->assertSame($connection, $conn);
            
            $pool->releaseConnection($conn);
        });
    }
}
