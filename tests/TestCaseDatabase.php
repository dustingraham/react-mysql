<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\ConnectionFactory;
use React\EventLoop\Factory;

class TestCaseDatabase extends TestCase
{
    /**
     * @var \PDO
     */
    protected static $pdo;
    /**
     * @var \mysqli
     */
    protected static $mysqli;
    /**
     * @var bool
     */
    protected static $initialized = false;
    
    public function setUp()
    {
        parent::setUp();
        
        ConnectionFactory::init(
            Factory::create(),
            $this->getCredentials()
        );
        
        $this->initDatabase();
    }
    
    protected function getCredentials()
    {
        $host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
        $user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'apache';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'apache';
        $name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'react_mysql_test';
        
        return [
            $host,
            $user,
            $pass,
            $name,
        ];
    }
    
    protected function initDatabase()
    {
        if (!self::$initialized)
        {
            // While this package is focused on mysqli async, we can use
            // PDO to initialize the database structure efficiently.
            $this->getPdoConnection()
                ->exec(file_get_contents(__DIR__ . '/sql.sql'));
            
            self::$initialized = true;
        }
    }
    
    protected function getPdoConnection()
    {
        if (is_null(self::$pdo))
        {
            list($host, $user, $pass, $name) = $this->getCredentials();
            $dsn = 'mysql:host=' . $host . ';dbname=' . $name;
            self::$pdo = new \PDO($dsn, $user, $pass);
        }
        
        return self::$pdo;
    }
    
    protected function getDatabase()
    {
        return new DatabaseMock();
    }
    
    /**
     * Note, do not close the connection. It is reused throughout the tests.
     *
     * @return \mysqli
     */
//    protected function getMysqliConnection()
//    {
//        if (is_null(self::$mysqli))
//        {
//            self::$mysqli = $this->getNewMysqliConnection();
//        }
//        
//        return self::$mysqli;
//    }
    
//    protected $connCount = 1;
//    protected function getNewMysqliConnection()
//    {
//        list($host, $user, $pass, $name) = $this->getCredentials();
//        $mysqli = new Thing($host, $user, $pass, $name);
//        $mysqli->countId = $this->connCount;
//        $this->connCount++;
//        return $mysqli;
//    }
}
