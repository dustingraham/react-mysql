<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\ConnectionFactory;

class ConnectionFactoryTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testMissingCredentials()
    {
        ConnectionFactory::init(null);
        
        ConnectionFactory::createConnection();
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testBadCredentials()
    {
        ConnectionFactory::init([
            'localhost',
            'bad.username',
            'bad.password',
            'fake',
        ]);
        
        ConnectionFactory::createConnection();
    }
}
