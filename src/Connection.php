<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;

class Connection extends \mysqli
{
    /**
     * @var int
     */
    protected static $nextId = 0;
    
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var bool|string
     */
    protected $currentQuery = false;
    
    public function __construct($host = null, $username = null, $passwd = null, $dbname = null, $port = null, $socket = null)
    {
        parent::__construct($host, $username, $passwd, $dbname, $port, $socket);
        
        $this->id = self::$nextId++;
    }
    
    /**
     * Proxy to the mysqli connection object.
     *
     * @param $string
     * @return string
     */
    public function escape($string)
    {
        return $this->real_escape_string($string);
    }
    
    public function execute(Command $command)
    {
        if ($this->currentQuery)
        {
            throw new \Exception('Another query is already pending for this connection.');
        }
        
        $this->currentQuery = $command->getPreparedQuery($this);
        
        $status = $this->mysqli->query($this->currentQuery, MYSQLI_ASYNC);
        if ($status === false)
        {
            throw new \Exception($this->mysqli->error);
        }
        
        $deferred = new Deferred();
        
        $this->loop->addPeriodicTimer(
            $this->pollInterval,
            function (TimerInterface $timer) use ($deferred)
            {
                $reads = $errors = $rejects = [$this->mysqli];
                
                // Non-blocking requires a zero wait time.
                $this->mysqli->poll($reads, $errors, $rejects, 0);
                
                $read = in_array($this->mysqli, $reads, true);
                $error = in_array($this->mysqli, $errors, true);
                $reject = in_array($this->mysqli, $rejects, true);
                
                if ($read)
                {
                    $result = $this->mysqli->reap_async_query();
                    if ($result === false)
                    {
                        $deferred->reject($this->mysqli->error);
                    }
                    else
                    {
                        // Success!!
                        $deferred->resolve($result);
                    }
                }
                else
                {
                    if ($error)
                    {
                        $deferred->reject($this->mysqli->error);
                    }
                    else
                    {
                        if ($reject)
                        {
                            $deferred->reject($this->mysqli->error);
                        }
                    }
                }
                
                // If poll yielded something for this connection, we're done!
                if ($read || $error || $reject)
                {
                    $this->currentQuery = false;
                    $timer->cancel();
                }
            }
        );
        
        return $deferred->promise();
    }
}
