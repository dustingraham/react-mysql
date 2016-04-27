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
        $quotedSql = $this->quoteIntoSql($connection); 
        
        return $quotedSql;
    }
    
    /**
     * TODO: This is exactly what I don't want to do. "Roll my own" SQL handler.
     * However, the requirements for this package have led to this point for now.
     * 
     * @param Connection $connection
     * @return mixed
     */
    protected function quoteIntoSql(Connection $connection)
    {
        $quotedSql = $this->sql;
        $quotedParams = [];
        
        foreach($this->params as $key => $value)
        {
            if (is_null($value))
            {
                $quotedParams[$key] = 'NULL';
            }
            else if (is_integer($value))
            {
                $quotedParams[$key] = (int)$value;
            }
            else if (in_array($value, $this->reserved_words))
            {
                $quotedParams[$key] = $value;
            }
            else
            {
                $quotedParams[$key] = '\''.$connection->escape($value).'\'';
            }
        }
        
        return strtr($quotedSql, $quotedParams);
    }
    
    // TODO: Find all of these...
    protected $reserved_words = [
        'NOW()'
    ];
    
    /**
     * @return \React\Promise\PromiseInterface
     */
    public function execute()
    {
        return $this->db->executeCommand($this);
    }
}
