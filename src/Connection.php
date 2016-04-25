<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;

class Connection
{
    /**
     * @var \mysqli
     */
    protected $mysqli;
    
    /**
     * @var LoopInterface
     */
    protected $loop;
    
    /**
     * @var float
     */
    protected $pollInterval = 0.01;
    
    public function __construct(\mysqli $mysqli, LoopInterface $loop)
    {
        $this->mysqli = $mysqli;
        $this->loop = $loop;
    }
    
    public function escape($data)
    {
        if (is_array($data))
        {
            $data = array_map([
                $this,
                'escape',
            ], $data);
        }
        else
        {
            $data = $this->mysqli->real_escape_string($data);
        }
        
        return $data;
    }
    
    public function execute(Command $command)
    {
        $query = $command->getPreparedQuery($this);
        
        $status = $this->mysqli->query($query, MYSQLI_ASYNC);
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
                        $deferred->reject(new \Exception($this->mysqli->error));
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
                        $deferred->reject(new \Exception($this->mysqli->error));
                    }
                    else
                    {
                        if ($reject)
                        {
                            $deferred->reject(new \Exception($this->mysqli->error));
                        }
                    }
                }
                
                // If poll yielded something for this connection, we're done!
                if ($read || $error || $reject)
                {
                    $timer->cancel();
                }
            }
        );
        
        return $deferred->promise();
    }
}
