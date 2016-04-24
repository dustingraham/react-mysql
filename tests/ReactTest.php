<?php namespace DustinGraham\ReactMysql\Tests;

class ReactTest extends TestCase
{
    public function testBasicLoop()
    {
        $this->markTestSkipped('Fix me');
        
        $loop = \React\EventLoop\Factory::create();
        $driver = new \DustinGraham\ReactMysql\Connection($loop);
        
        $that = $this;
        $driver->query('SELECT * FROM users;')->then(
            function (\mysqli_result $result) use ($that)
            {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $result->close();
                
                $that->assertCount(2, count($rows));
            },
            function () use ($that)
            {
                $that->fail('Query failed.');
            });
        
        //$loop->tick();
    }
}
