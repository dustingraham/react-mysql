<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Database;
use React\EventLoop\Timer\TimerInterface;

class DatabaseMock extends Database
{
    public $loops = 0;
    
    public function loopTick(TimerInterface $timer)
    {
        // Expect tests not to take too long.
        $this->loops++;
        if ($this->loops > 200)
        {
            throw new \Exception('time out failure');
        }
        
        parent::loopTick($timer);
    }
}
