<?php namespace DustinGraham\ReactMysql;

use React\Promise\Deferred;

class ConnectionPool
{
    /**
     * @var int
     */
    protected $maxConnections = 100;
    
    /**
     * @var \SplObjectStorage
     */
    protected $pool;
    
    /**
     * Queue, first in first out for available connections.
     * @var \SplQueue
     */
    protected $available;
    
    /**
     * Queue, first in first out for waiting.
     * @var \SplQueue
     */
    protected $waiting;
    
    public function __construct()
    {
        $this->pool = new \SplObjectStorage();
        $this->available = new \SplQueue();
        $this->waiting = new \SplQueue();
    }
    
    public function withConnection($cb)
    {
        // First check idle connections.
        if ($this->available->count() > 0)
        {
            $connection = $this->available->dequeue();
            
            $cb($connection);
            
            return;
        }
        
        // Check if we have max connections
        if ($this->pool->count() >= $this->maxConnections)
        {
            $this->waiting->enqueue($cb);
        }
        
        // Otherwise, create a new connection
        $connection = ConnectionFactory::createConnection();
        
        $this->pool->attach($connection);
        
        $cb($connection);
    }
    
    /**
     * We use a promise in case all connections are busy.
     *
     * @deprecated Use withConnection
     * @return \React\Promise\Promise
     */
    public function getConnection()
    {
        // First check idle connections.
        if ($this->available->count() > 0)
        {
            $connection = $this->available->dequeue();
            
            return \React\Promise\resolve($connection);
        }
        
        // Check if we have max connections
        if ($this->pool->count() >= $this->maxConnections)
        {
            $deferred = new Deferred();
            $this->waiting->enqueue($deferred);
            
            return $deferred->promise();
        }
        
        // Otherwise, create a new connection
        $connection = ConnectionFactory::createConnection();
        
        $this->pool->attach($connection);
        
        return \React\Promise\resolve($connection);
    }
    
    /**
     * Once a connection has finished being used...
     * @param Connection $connection
     */
    public function releaseConnection(Connection $connection)
    {
        // If we have any promises waiting for the connection, pass it along.
        if ($this->waiting->count() > 0)
        {
            $this->waiting->dequeue()->resolve($connection);
        }
        
        // Otherwise, move it to the idle queue.
        $this->available->enqueue($connection);
    }
}
