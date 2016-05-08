<?php namespace DustinGraham\ReactMysql\Tests;

use DustinGraham\ReactMysql\Command;
use DustinGraham\ReactMysql\ConnectionFactory;

class CommandTest extends TestCase
{
    public function testComplexBind()
    {
        $command = new Command("
          INSERT INTO simple_table (
            `id`,
            `name`,
            `value`,
            `created_at`
          ) VALUES (
            :id,
            :name,
            :num,
            :datetime
          );
        ", [
            ':id' => null,
            ':name' => 'John\'s Name',
            ':num' => 7,
            ':datetime' => 'NOW()',
        ]);
        
        $connection = ConnectionFactory::createConnection();
        
        $query = $command->getPreparedQuery($connection);
        
        $this->assertStringEqualsIgnoreSpacing(
            "INSERT INTO simple_table ( `id`, `name`, `value`, `created_at` ) VALUES ( NULL, 'John\'s Name', 7, NOW() );",
            $query
        );
    }
    
    public function testAssertStrings()
    {
        $this->assertStringEqualsIgnoreSpacing('yes no  ', 'yes  no');
    }
    
    public function testSingleBind()
    {
        $command = new Command("
            SELECT * FROM simple_table WHERE id = :id
        ");
        
        $command->bind(':id', 1);
        
        $connection = ConnectionFactory::createConnection();
        
        $command->getPreparedQuery($connection);
    }
    
    public function testParameterReplacing()
    {
        $command = new Command;
        $command->sql = 'SELECT * FROM simple_table WHERE id = :id';
        $command->bind(':id', 2);
        
        $connection = ConnectionFactory::createConnection();
        
        $query = $command->getPreparedQuery($connection);
        
        $this->assertStringEqualsIgnoreSpacing(
            'SELECT * FROM simple_table WHERE id = 2',
            $query
        );
    }
    
    /**
     * TODO: This test is still todo.
     *
     * @throws \Exception
     */
    public function testParamCounting()
    {
        // Note: Used a comma rather than => so it was failing.
        // param count would detect this sooner.
        
        // Intentionally bad parameters to ensure check.
        $badParams = [':test', 1,];
        // The programmer's intent was:
        // $goodParams = [ ':test' => 1, ]
        
        $command = new Command(
            'SELECT * FROM simple_table WHERE id = :test',
            $badParams
        );
        
        $connection = ConnectionFactory::createConnection();
        
        $query = $command->getPreparedQuery($connection);
        
        // TODO: Here is the bad result, :test should have been 1
        // TODO: GetPreparedQuery should error on param mismatch
        $this->assertStringEqualsIgnoreSpacing(
            'SELECT * FROM simple_table WHERE id = :test',
            $query
        );
    }
}
