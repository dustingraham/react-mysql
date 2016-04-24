# ReactMysql

Non-blocking MySQLi database access with PHP.
Designed to work with [reactphp/react](https://github.com/reactphp/react).


## Working

This __is__ working. But it is nowhere near complete. 

    $ ./run
    Starting loop...
    DB Created.
    Run Query: 0
    Found rows: 0
    Run Query: 1
    Found rows: 1
    Current memory usage: 735.117K
    Run Query: 2
    Found rows: 0
    Run Query: 3
    Found rows: 1
    Run Query: 4
    Found rows: 1
    Current memory usage: 735.117K
    Run Query: 5
    Found rows: 0
    Current memory usage: 733.602K
    Current memory usage: 733.602K
    Current memory usage: 733.602K
    Loop finished, all timers halted.

This won't work out of the box without the database configured.
As of this point, database configuration is hard coded.
Still need to pull out the configs. You will also need to
set up a database with some data to query. Check back later
for more!

## TODO

A lot.

This is not production ready. Still tons to do on the query builder.
While I hate to reinvent the wheel, I have not found a lightweight
injectable query builder that is not tied to a massive framework.

## Plans (Future Examples)

These are just plans for now. It may change wildly as we develop.

### Current Development Example

Here is an example of what is currently working for the most part.

    $loop = React\EventLoop\Factory::create();
    
    ConnectionFactory::init($loop);
    
    $db = new \DustinGraham\ReactMysql\Database();  
    
    $db->createCommand("SELECT * FROM `table` WHERE id = :id;", [':id' => $id])
      ->execute()->then(
        function($result)
        {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->close();
            
            // Do something with $rows.
        }
    );
    

### Original Big Picture Plans

Here are some examples of how it may be, eventually.
It would be nice to hide away some of the current boilerplate.

    Connection::init($loop);
    
    Connection::query(
      'SELECT * FROM `table` WHERE `column` = ? AND `column2` = ?;',
      ['red', 'white']
    )->then(function($result) { ... });
    
    Connection::query(...) returns a promise.
    
    $db = new Database();
    $db->createCommand('SELECT * FROM table WHERE id = :id', [':id' => 1])
      ->execute()
      ->then(function($results) {
          echo $results[0]->name;
      });
    

And another idea...

    DB::loadModel('id', ' =', '3')->then(function($model) use ($socket) {
      $socket->send('Your name is '.$model->name);
    });

## Difficulties

There were many difficulties.

At this point, I can not find any libraries that handle parameterized queries
without using PDO or prepared statements.

MYSQLI_ASYNC does not support prepared statements and parameter binding. So we had to write it ourselves.

The mysqli::real_escape_string requires a link. But, the link is one of many.
Last minute escaping once the command and connection were married from the pool.
Could potentially have one dedicated link for escaping.

## Credits

Much appreciation to the hard work over at [reactphp/react](https://github.com/reactphp/react).

Inspired by similar projects:
 - [kaja47/async-mysql](https://github.com/kaja47/async-mysql)
 - [bixuehujin/reactphp-mysql](https://github.com/bixuehujin/reactphp-mysql)

## License

DustinGraham/ReactMysql is released under the [MIT](https://github.com/dustingraham/react-mysql/blob/master/LICENSE) license.
