<?php namespace DustinGraham\ReactMysql;

class Connection extends \mysqli
{
    /**
     * @var int
     */
    protected static $nextId = 0;
    
    /**
     * Used to differentiate mysqli connections without depending on thread_id.
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
}
