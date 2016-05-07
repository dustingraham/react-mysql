<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\Connection;
use DustinGraham\ReactMysql\ConnectionFactory;
use DustinGraham\ReactMysql\Database;
use React\EventLoop\Factory;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\UnhandledRejectionException;

class RebuildTest extends TestCaseDatabase
{
    public function testOne()
    {
        $this->assertTrue(true);
    }
    
    public function testWithClasses()
    {
        $db = $this->getDatabase();
        
        for($loops = 0; $loops < 3; $loops++)
        {
            for ($i = 0; $i < 3; $i++)
            {
                $sql = 'SELECT * FROM simple_table WHERE id = ' . $i;
                //$sql = 'SELECT SLEEP(0.1);';
                $db->statement($sql)->then(function (\mysqli_result $result)
                {
                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                    $this->assertLessThanOrEqual(1, count($rows));
                    
                    //$rowCount = count($rows);
                    //echo $rowCount;
                })->done();
            }
            
            while(count($db->conns))
            {
                usleep(1000);
                $db->loop->tick();
            }
        }
    }
    
    public function testShutdownWithNothing()
    {
        $db = $this->getDatabase();
        
        $db->shuttingDown = true;
        $db->loop->run();
    }
    
    public function testWithSleepFail()
    {
        $db = $this->getDatabase();
        
        $errorCount = 0;
        
        $queries = [
            'SELECT * FROM simple_table WHERE id = 1',
            'SELECT foo FROM',
            'SELECT SLEEP(0.2);',
            'SELECT foo FROM',
            'SELECT SLEEP(0.3);',
            'SELECT foo FROM',
            'SELECT SLEEP(0.1);',
        ];
        foreach($queries as $sql)
        {
            $db->statement($sql)->then(function(\mysqli_result $result)
            {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $this->assertCount(1, $rows);
            })
            ->otherwise(function($error) use (&$errorCount) {
                $errorCount++;
            })->done();
        }
        
        $db->shuttingDown = true;
        $db->loop->run();
        
        $this->assertSame(3, $errorCount);
    }
    
    public function testSimpleBind()
    {
        $db = $this->getDatabase();
        
        $db->statement('SELECT * FROM simple_table WHERE id = :test', [':test' => 2])
            ->then(function(\mysqli_result $result)
            {
                $this->assertCount(1, $result->fetch_all(MYSQLI_ASSOC));
            })
            ->done();
        
        $db->shuttingDown = true;
        $db->loop->run();
    }
    
    public function testFreeResult()
    {
        $db = $this->getDatabase();
        
        $db->statement('SELECT * FROM simple_table WHERE id = :test', [':test' => 2])
            ->then(function(\mysqli_result $result)
            {
                $this->assertCount(1, $result->fetch_all(MYSQLI_ASSOC));
                
                // Ensure warning is not thrown.
                $result->free();
            })
            ->done();
        
        $db->shuttingDown = true;
        $db->loop->run();
    }
    
    public function testBadQuery()
    {
        $db = $this->getDatabase();
        
        $errorTriggered = false;
        $db->statement('SELECT foo FROM')
            ->then(function(\mysqli_result $result)
            {
                $this->fail();
            })
            ->otherwise(function($error) use (&$errorTriggered)
            {
                $errorTriggered = !!$error;
            })
            ->done();
        
        $db->shuttingDown = true;
        $db->loop->run();
        
        $this->assertTrue($errorTriggered, 'Error was sent to otherwise callback.');
    }
    
    public function testUnhandledBadQuery()
    {
        $db = $this->getDatabase();
        
        $db->statement('SELECT foo FROM')
            ->then(function(\mysqli_result $result)
            {
                $this->fail();
            })
            ->done();
        
        $this->setExpectedException(UnhandledRejectionException::class);
        
        $db->shuttingDown = true;
        $db->loop->run();
    }
    
    /**
     * Works Brilliantly
     */
    public function disabled_testTheConcept()
    {
        echo PHP_EOL;
        $pool = [];
        
        for($i = 0; $i < 0; $i++)
        {
            $mysqli = $this->getNewMysqliConnection();
            $mysqli_id = $mysqli->thread_id;
            $mysqli_my = $mysqli->countId;
            
            $sql = 'SELECT SLEEP('.$i.');';
            
            $mysqli->query($sql, MYSQLI_ASYNC);
            
            $deferred = new Deferred();
            
            $pool[$mysqli->thread_id] = [
                'mysqli' => $mysqli,
                'deferred' => $deferred,
            ];
            
            $deferred->promise()->then(function(\mysqli_result $result) use (&$rowCount)
            {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $rowCount = count($rows);
                
                echo $rowCount;
            });
        }
        
        for($i = 0; $i < 3; $i++)
        {
            $mysqli = $this->getNewMysqliConnection();
            $mysqli_id = $mysqli->thread_id;
            
            $sql = 'SELECT foo FROM';
            
            $mysqli->query($sql, MYSQLI_ASYNC);
            
            $deferred = new Deferred();
            
            $pool[$mysqli->thread_id] = [
                'mysqli' => $mysqli,
                'deferred' => $deferred,
            ];
            
            $deferred->promise()->then(function(\mysqli_result $result) use (&$rowCount) {
                echo 'M';
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $rowCount = count($rows);
                
                echo $rowCount;
                
                $result->close();
            });
        }
        
        for($i = 0; $i < 6; $i++)
        {
            $mysqli = $this->getNewMysqliConnection();
            $mysqli_id = $mysqli->thread_id;
            
            $sql = 'SELECT * FROM simple_table WHERE id = '.$i;
            
            $mysqli->query($sql, MYSQLI_ASYNC);
            
            $deferred = new Deferred();
            
            $pool[$mysqli->thread_id] = [
                'mysqli' => $mysqli,
                'deferred' => $deferred,
            ];
            
            $deferred->promise()->then(function(\mysqli_result $result) use (&$rowCount) {
                
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                $rowCount = count($rows);
                
                echo $rowCount;
                
                $result->close();
            });
        }
        
        $loop = Factory::create();
        
        $loop->addPeriodicTimer(0.01, function($timer) use (&$pool)
        {
            $reads = [];
            foreach($pool as $p)
            {
                $reads[] = $p['mysqli'];
            }
            
            if (count($reads) < 1) return;
            
            if (mysqli_poll($reads, $errors = [], $rejects = [], 0) < 1) return;
            
            echo '('.count($reads).'/'.count($errors).'/'.count($rejects).')';
            
            /** @var \mysqli $read */
            foreach($reads as $read)
            {
                //echo '{'.$read->thread_id.'}';
                $deferred = $pool[$read->thread_id]['deferred'];
                $result = $read->reap_async_query();
                if ($result === false)
                {
                    echo 'W';
                }
                $deferred->resolve($result);
                
                unset($pool[$read->thread_id]);
            }
            
            foreach($errors as $error)
            {
                echo 'A';
                unset($pool[$error->thread_id]);
            }
            
            foreach($rejects as $reject)
            {
                echo 'B';
                unset($pool[$reject->thread_id]);
            }
            
            if (count($pool) == 0)
            {
                $timer->cancel();
            }
        });
        
        $loop->run();
        
        //$this->assertEquals(1, $rowCount);
        
        //$this->assertEquals($mysqli_id, $mysqli->thread_id);
    }
    
    
    public function XtestExtendedAssert()
    {
        foreach ([
                     [
                         'a b',
                         'a c',
                     ],
                     [
                         'alpha beta',
                         'alpha    delta',
                     ],
                     [
                         'ab',
                         'a b',
                     ],
                     [
                         ' a bc',
                         ' abc',
                     ],
                 ] as $test)
        {
            $this->assertStringNotEqualsIgnoreSpacing($test[0], $test[1]);
        }
        
        foreach ([
                     [
                         // variable internal spacing
                         'a  b',
                         'a     b',
                     ],
                     [
                         // variable spacing, longer text, more instances
                         'alpha beta  delta      gamma',
                         'alpha    beta delta   gamma',
                     ],
                     [
                         // Trailing and Leading spaces.
                         '  a  b c',
                         'a  b  c ',
                     ],
                 ] as $test)
        {
            $this->assertStringEqualsIgnoreSpacing($test[0], $test[1]);
        }
    }
}
