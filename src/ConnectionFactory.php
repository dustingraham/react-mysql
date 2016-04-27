<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\LoopInterface;

class ConnectionFactory
{
    /**
     * @var LoopInterface
     */
    public static $loop;
    
    /**
     * @var array
     */
    protected static $credentials;
    
    public static function init(LoopInterface $loop, $credentials)
    {
        self::$loop = $loop;
        self::$credentials = $credentials;
    }
    
    public static function createConnection()
    {
        if (is_null(self::$loop))
        {
            throw new \Exception('Loop not provided.');
        }
        
        $mysqli = new \mysqli(
            self::$credentials[0],
            self::$credentials[1],
            self::$credentials[2],
            self::$credentials[3]
        );
        
        if ($mysqli === false)
        {
            throw new \Exception(mysqli_connect_error());
        }
        
        $connection = new Connection($mysqli, self::$loop);
        
        return $connection;
    }
}
