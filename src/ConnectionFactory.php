<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\LoopInterface;

class ConnectionFactory
{
    /**
     * @var LoopInterface
     */
    //public static $loop;
    
    /**
     * @var array
     */
    protected static $credentials;
    
    public static function init(LoopInterface $loop, $credentials)
    {
        //self::$loop = $loop;
        self::$credentials = $credentials;
    }
    
    public static function createConnection()
    {
        $connection = new Connection(
            self::$credentials[0],
            self::$credentials[1],
            self::$credentials[2],
            self::$credentials[3]
        );
        
        if ($connection === false)
        {
            throw new \Exception(mysqli_connect_error());
        }
        
        return $connection;
    }
}
