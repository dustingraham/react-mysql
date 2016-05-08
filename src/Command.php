<?php namespace DustinGraham\ReactMysql;

class Command
{
    /**
     * @var string
     */
    public $sql;
    
    /**
     * @var array
     */
    protected $params = [];
    
    /**
     * TODO: Find all of these
     *
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
     * @param Connection $connection
     * @return string
     */
    public function getPreparedQuery(Connection $connection)
    {
        return $this->quoteIntoSql($connection);
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
}
