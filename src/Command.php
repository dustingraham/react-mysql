<?php namespace DustinGraham\ReactMysql;

class Command
{
    /**
     * @var Database the command is associated with.
     */
    public $db;
    
    /**
     * @var string
     */
    public $sql;
    
    /**
     * @var array
     */
    protected $params = [];
    
    public function __construct(Database $database, $sql = null)
    {
        $this->db = $database;
        $this->sql = $sql;
    }
    
    /**
     * @param string|array $key
     * @param string|null $value
     * @return $this
     */
    public function bind($key, $value = null)
    {
        if (is_array($key))
        {
            // TODO: Is this cludgy?
            $this->bindValues($key);
        }
        else
        {
            $this->params[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * @param $params
     * @return $this
     */
    public function bindValues($params)
    {
        foreach ($params as $k => $v)
        {
            $this->params[$k] = $v;
        }
        
        return $this;
    }
    
    /**
     * @param Connection $connection
     * @return string
     */
    public function getPreparedQuery(Connection $connection)
    {
        $this->params = $connection->escape($this->params);
        
        return strtr($this->sql, $this->params);
    }
    
    /**
     * @return \React\Promise\PromiseInterface
     */
    public function execute()
    {
        return $this->db->executeCommand($this);
    }
}
