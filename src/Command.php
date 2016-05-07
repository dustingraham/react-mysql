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
    
    /**
     * @var array
     */
    protected $reserved_words = [
        'NOW()',
    ];
    
    public function __construct($sql = null, $params = null)
    {
        $this->sql = $sql;
        $this->bind($params);
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
            foreach ($key as $k => $v)
            {
                $this->params[$k] = $v;
            }
        }
        else if (!is_null($key))
        {
            $this->params[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * @deprecated 
     * 
     * @param $params
     * @return $this
     */
    public function bindValues($params)
    {
        return $this->bind($params);
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
    
    // TODO: Find all of these...
    
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
        
        foreach ($this->params as $key => $value)
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
                $quotedParams[$key] = '\'' . $connection->escape($value) . '\'';
            }
        }
        
        return strtr($quotedSql, $quotedParams);
    }
    
    /**
     * @deprecated 
     * 
     * @return \React\Promise\Promise
     */
    public function execute()
    {
        $thing = $this->db->executeCommand($this);
        
        return $thing;
    }
}
