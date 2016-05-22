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
    
    public function __construct($credentials = null, $loop = null)
    {
        if (!is_null($credentials))
        {
            ConnectionFactory::init($credentials);
        }
        
        // Use the provided loop, otherwise create one.
        $this->loop = $loop ?: Factory::create();
        
        $this->initLoop();
        
        $this->pool = new ConnectionPool();
    }
    
    public function statement($sql, $params = null)
    {
        $command = new Command($sql, $params);
        
        $deferred = new Deferred();
        
        $this->pool->withConnection(function (Connection $connection) use ($command, $deferred)
        {
            $sql = $command->getPreparedQuery($connection);
            
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
                // TODO: Possible race condition if shutdown also queues queries, such as a final save.
                // This could be prematurely cancelled.
                $timer->cancel();
            }
            
            // Nothing in the queue.
            return;
        }
        
        $reads = $errors = $rejects = [];
        foreach ($this->conns as $conn)
        {
            $reads[] = $conn['mysqli'];
        }
        
        // Returns immediately, the non-blocking magic!
        if (mysqli_poll($reads, $errors, $rejects, 0) < 1) return;
        
        /** @var Connection $read */
        foreach ($reads as $read)
        {
            /** @var Deferred $deferred */
            $deferred = $this->conns[$read->id]['deferred'];
            $result = $read->reap_async_query();
            if ($result !== false)
            {
                $deferred->resolve($result);
                
                // $result is true for non-select queries.
                if ($result instanceof \mysqli_result)
                {
                    // If userland code has already freed the result, this will throw a warning.
                    // No need to throw a warning here...
                    // If you know how to check if the result has already been freed, please PR!
                    @$result->free();
                }
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
        foreach ($errors as $error)
        {
            $this->pool->releaseConnection($error);
            unset($this->conns[$error->id]);
            
            throw new \Exception('Unexpected mysqli_poll $error.');
        }
        
        // Check rejection pile.
        // Current understanding is that this would only happen if we passed a
        // connection that was already reaped. But... maybe not.
        foreach ($rejects as $reject)
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
