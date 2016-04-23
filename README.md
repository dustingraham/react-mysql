# react-mysql
Nuclear MySQL Reactor


# Examples

Connection::init($loop);

Connection::query('SELECT * FROM `table` WHERE `column` = ? AND `column2` = ?;', ['red', 'white'])
    ->then(function($result) { ... });

Connection::query returns a promise. This has all of the normal promise interface options.

# Credits
Inspiration from:
 - https://github.com/kaja47/async-mysql
 - https://github.com/bixuehujin/reactphp-mysql
