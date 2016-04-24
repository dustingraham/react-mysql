<?php namespace DustinGraham\ReactMysql;

use React\Promise\Deferred;

class Database
{
    protected $pool;
    
    public function __construct()
    {
        $this->pool = new ConnectionPool();
    }
    
    /**
     * @param string|null $sql
     * @param array $params
     * @return Command
     */
    public function createCommand($sql = null, $params = [])
    {
        $command = new Command($this, $sql);
        
        return $command->bindValues($params);
    }
    
    /**
     * @param Command $command
     * @return \React\Promise\PromiseInterface
     */
    public function executeCommand(Command $command)
    {
        $deferred = new Deferred();
        
        $this->pool->getConnection()
            ->then(function (Connection $connection) use ($command, $deferred)
            {
                // Connection was retrieved from the pool. Execute the command.
                $connection->execute($command)
                    ->then(function (\mysqli_result $result) use ($deferred)
                    {
                        // We must resolve first so that the result can be closed.
                        $deferred->resolve($result);
                        
                        // Doesn't hurt to close it again.
                        $result->close();
                    })
                    ->always(function () use ($connection)
                    {
                        // Ensure we always return the connection to the pool.
                        $this->pool->releaseConnection($connection);
                    });
            });
        
        return $deferred->promise();
    }
}
