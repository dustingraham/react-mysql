<?php namespace DustinGraham\ReactMysql;

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
     * Once a connection has finished being used...
     * @param Connection $connection
     */
    public function releaseConnection(Connection $connection)
    {
        // If we have any promises waiting for the connection, pass it along.
        if ($this->waiting->count() > 0)
        {
            $cb = $this->waiting->dequeue();
            
            $cb($connection);
            
            return;
        }
        
        // Otherwise, move it to the idle queue.
        $this->available->enqueue($connection);
    }
}
