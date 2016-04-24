<?php namespace DustinGraham\ReactMysql;

use React\EventLoop\LoopInterface;

class ConnectionFactory
{
    /**
     * @var LoopInterface
     */
    protected static $loop;
    
    public static function init(LoopInterface $loop)
    {
        self::$loop = $loop;
    }
    
    public static function createConnection()
    {
        if (is_null(self::$loop))
        {
            throw new \Exception('Loop not provided.');
        }
        
        //$mysqli = mysqli_connect('localhost', 'user', 'pass', 'dbname');
        $mysqli = mysqli_connect('localhost', 'react', 'react', 'react');
        
        if ($mysqli === false)
        {
            throw new \Exception(mysqli_connect_error());
        }
        
        $connection = new Connection($mysqli, self::$loop);
        
        return $connection;
    }
}
