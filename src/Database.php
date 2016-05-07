<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;

class Database
{
    /**
     * @var ConnectionPool
     */
    protected $pool;
    
    /**
     * @var LoopInterface
     */
    public $loop;
    
    /**
     * @var float
     */
    protected $pollInterval = 0.01;
    
    
    public function __construct()
    {
        $this->loop = Factory::create();
        $this->initLoop();
        
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
     * @deprecated Use statement
     * @param Command $command
     * @return \React\Promise\Promise
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
                    ->otherwise(function ($reason) use ($deferred)
                    {
                        // If the connection execution fails, pass the failure back to the command.
                        $deferred->reject($reason);
                    })
                    ->always(function () use ($connection)
                    {
                        // Ensure we always return the connection to the pool.
                        $this->pool->releaseConnection($connection);
                    });
            });
        
        return $deferred->promise();
    }
    
    /**
     * @deprecated Remove from tests.
     * 
     * @return ConnectionPool
     */
    public function getPool()
    {
        return $this->pool;
    }
    
    public function statement($sql)
    {
        $deferred = new Deferred();
        
        $this->pool->withConnection(function($connection) use ($sql, $deferred)
        {
            $connection->query($sql, MYSQLI_ASYNC);
            
            $this->conns[$connection->id] = [
                'mysqli' => $connection,
                'deferred' => $deferred,
            ];
        });
        
        return $deferred->promise();
    }
    
    public function initLoop()
    {
        $this->loop->addPeriodicTimer(
            $this->pollInterval,
            [$this, 'loopTick']
        );
    }
    
    public $conns = [];
    
    public $shuttingDown = false;
    
    public function loopTick(TimerInterface $timer)
    {
        if (count($this->conns) == 0)
        {
            // If we are shutting down, and have nothing to check, kill the timer.
            if ($this->shuttingDown)
            {
                $timer->cancel();
            }
            
            // Nothing in the queue.
            return;
        }
        
        $reads = $errors = $rejects = [];
        foreach($this->conns as $conn)
        {
            $reads[] = $conn['mysqli'];
        }
        
        // Returns immediately, the non-blocking magic!
        if (mysqli_poll($reads, $errors, $rejects, 0) < 1) return;
        
        /** @var Connection $read */
        foreach($reads as $read)
        {
            /** @var Deferred $deferred */
            $deferred = $this->conns[$read->id]['deferred'];
            $result = $read->reap_async_query();
            if ($result !== false)
            {
                $deferred->resolve($result);
                $result->free();
            }
            else
            {
                $deferred->reject($read->error);
            }
            
            // Release the connection
            $this->pool->releaseConnection($read);
            
            unset($this->conns[$read->id]);
        }
        
        // Check error pile.
        // Current understanding is that this would only happen if the connection
        // was closed, or not opened correctly.
        foreach($errors as $error)
        {
            $this->pool->releaseConnection($error);
            unset($this->conns[$error->id]);
            
            throw new \Exception('Unexpected mysqli_poll $error.');
        }
        
        // Check rejection pile.
        // Current understanding is that this would only happen if we passed a
        // connection that was already reaped. But... maybe not.
        foreach($rejects as $reject)
        {
            $this->pool->releaseConnection($reject);
            unset($this->conns[$reject->id]);
            
            throw new \Exception('Unexpected mysqli_poll $reject.');
        }
        
        // Duplicated check to avoid one extra tick!
        // If we are shutting down, cancel timer once connections finish.
        if ($this->shuttingDown && count($this->conns) == 0)
        {
            $timer->cancel();
        }
    }
}
