<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\LoopInterface;

class ConnectionFactory
{
    /**
     * @var array
     */
    protected static $credentials;
    
    public static function init($credentials)
    {
        self::$credentials = $credentials;
    }
    
    public static function createConnection()
    {
        if (is_null(self::$credentials))
        {
            throw new \Exception('Database credentials not set.');
        }
        
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
