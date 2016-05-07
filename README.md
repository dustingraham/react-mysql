# ReactMysql

Non-blocking MySQLi database access with PHP.
Designed to work with [reactphp/react](https://github.com/reactphp/react).

[![Build Status](https://travis-ci.org/dustingraham/react-mysql.svg?branch=master)](https://travis-ci.org/dustingraham/react-mysql)

## Quickstart

    $db = new \DustinGraham\ReactMysql\Database(
        ['localhost', 'apache', 'apache', 'react_mysql_test']
    );
    
    $db->statement('SELECT * FROM simple_table WHERE id = :test', [':test' => 2])
        ->then(function(\mysqli_result $result)
        {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
        });
    
    $db->shuttingDown = true;
    $db->loop->run();

Setting `shuttingDown` to true will allow the loop to exit once the query has resolved.

## Working

This __is__ working. But it is nowhere near complete. Check out the example file
as well as the unit tests for more examples.

    $ ./example 
    Creating database....done!
    Run Query: 0
    Found rows: 0
    Run Query: 1
    Found rows: 1
    Current memory usage: 868.164K
    Run Query: 2
    Found rows: 1
    Run Query: 3
    Found rows: 1
    Run Query: 4
    Found rows: 0
    Current memory usage: 868.164K
    Run Query: 5
    Found rows: 0
    Current memory usage: 865.719K
    Current memory usage: 865.719K
    Current memory usage: 865.719K
    Loop finished, all timers halted.

This won't work out of the box without the database configured.
You will also need to set up a database with some data to query.

## Unit Tests

The example and unit tests expect a database called `react_mysql_test` which it
will populate with the proper tables each time it runs. It also expects `localhost`
and a user `apache` with password `apache`.

## TODO

A lot.

This is not production ready. Still tons to do on the query builder.
While I hate to reinvent the wheel, I have not found a lightweight
injectable query builder that is not tied to a massive framework.

## Plans (Future Examples)

These are just plans for now. It may change wildly as we develop.

### Current Development Example

Here is an example of what is currently working for the most part.

    $db = new \DustinGraham\ReactMysql\Database(
        ['localhost', 'apache', 'apache', 'react_mysql_test']
    );
    
    $db->statement('SELECT * FROM simple_table WHERE id = :test', [':test' => 2])
        ->then(function(\mysqli_result $result)
        {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            // Do something with $rows.
        });
    
    $db->shuttingDown = true;
    $db->loop->run();

### Original Big Picture Plans

Here are some examples of how it may be, eventually.
It would be nice to hide away some of the current boilerplate.

    Connection::init($loop, ['db_host', 'db_user', 'db_pass', 'db_name']);
    
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

### Query Building Support

Many MySQL wrapper packages have been analyzed, but none are completely independent of
a connection object that could be found.

For now, we will escape parameters, but require the user to provide a sql query that quotes the parameters.

This is obviously sub-optimal since a variable like $created_at could be NOW() or '2016-01-01' or NULL. 

The litmus test I have been using is the following query:

    INSERT INTO `simple_table` (`id`, `name`, `value`, `created_at`)
    VALUES (NULL, 'John\'s Name', 7, NOW());

The key points here are:

 - Support for putting the parameter in quotes! This is the first step. The rest is intelligently knowing when not to quote.
 - Support for a null value converted to NULL.
 - Support for escaping the parameter using either \\\' or '' is fine.
 - Support for not escaping functions such as NOW()
 - Support for recognizing integer values. Optional, since '7' will work fine.

### Wrapper Options Reviewed

 1. [nilportugues/php-sql-query-builder](https://github.com/nilportugues/php-sql-query-builder) - No connection required! But, odd syntax.
 1. [usmanhalalit/pixie](https://github.com/usmanhalalit/pixie) - Requires connection. Pretty close to needs.
 1. [joshcam/PHP-MySQLi-Database-Class](https://github.com/joshcam/PHP-MySQLi-Database-Class) - Requires connection.
 1. [aviat4ion/Query](https://git.timshomepage.net/aviat4ion/Query) - Requires connection.
 1. [rkrx/php-mysql-query-builder](https://github.com/rkrx/php-mysql-query-builder) - Requires connection.
 1. [stefangabos/Zebra_Database](https://github.com/stefangabos/Zebra_Database) - Requires connection, does more than needed.
 1. [indeyets/MySQL-Query-Builder](https://github.com/indeyets/MySQL-Query-Builder) - Not maintained. Odd syntax.

The nilportugues/php-sql-query-builder package is very close, but it does not quote the parameters.

## Install

The recommended way to install this library is through Composer.

    $ composer require dustingraham/react-mysql

## Credits

Much appreciation to the hard work over at [reactphp/react](https://github.com/reactphp/react).

Inspired by similar projects:
 - [kaja47/async-mysql](https://github.com/kaja47/async-mysql)
 - [bixuehujin/reactphp-mysql](https://github.com/bixuehujin/reactphp-mysql)

## License

DustinGraham/ReactMysql is released under the [MIT](https://github.com/dustingraham/react-mysql/blob/master/LICENSE) license.
