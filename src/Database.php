<?php
/**
 * Database wrapper for PDO
 *
 * @author  Jared Howland <database@jaredhowland.com>
 * @version 2023-04-12
 * @since   2017-03-16
 */

namespace Database;

use PDO;
use RuntimeException;

/**
 * Database class
 */
class Database
{
    private $driver;
    private $host;
    private $port;
    private $dbName;
    private $unixSocket;
    private $charset;
    private $dbPath;
    private $username;
    private $password;
    private $options;

    private $dsn;
    private $db;

    private $action;
    private $bind;
    private $result;

    /**
     * Define the database port to use.
     *
     * @param int $port Optional. Defines the database port number to use. Default: `null`. Valid options: `null`, any
     *                  integer from `0` to `1024` inclusive.
     *
     * @return object $this
     *
     * @throws RuntimeException if an invalid port number is used.
     */
    public function port($port = null)
    {
        if (($port >= 0 && $port <= 1024) || $port === null) {
            $this->port = $port;

            return $this;
        }

        throw new RuntimeException('Invalid port number. Must be `null` or an integer ranging between 0 and inclusive.');
    }

    /**
     * Define the database name to use.
     *
     * @param string $dbName Defines the database name to use.
     *
     * @return object $this
     */
    public function dbName($dbName)
    {
        $this->dbName = $this->validateString($dbName, 'Database name');

        return $this;
    }

    /**
     * Define the Unix socket to use.
     *
     * @param string $unixSocket Optional. Defines the Unix socket to use. Default: `null`.
     *
     * @return object $this
     */
    public function unixSocket($unixSocket = null)
    {
        $this->unixSocket = $this->validateString($unixSocket, 'Unix socket');

        return $this;
    }

    /**
     * Charset to use when connecting to database
     *
     * @param string $charset Charset to use. Default: `utf8`
     *
     * @return object $this
     */
    public function charset($charset = 'utf8')
    {
        $this->charset = $this->validateString($charset, 'charset');

        return $this;
    }

    /**
     * Path to SQLite database
     *
     * @param string $dbPath Path to SQLite database
     *
     * @return object $this
     *
     * @throws RuntimeException if cannot connect to database
     */
    public function dbPath($dbPath)
    {
        if ($dbPath === ':memory:' || file_exists($dbPath)) {
            $this->dbPath = $dbPath;

            return $this;
        }

        throw new RuntimeException("You have entered an invalid path (`$dbPath`) to the database file.");
    }

    /**
     * Username to connect to the database
     *
     * @param string $username Username to connect to the database
     *
     * @return object $this
     */
    public function username($username)
    {
        $this->username = $this->validateString($username, 'Username');

        return $this;
    }

    /**
     * Password to connect to the database
     *
     * @param string $password Password to connect to the database
     *
     * @return object $this
     */
    public function password($password)
    {
        $this->password = $this->validateString($password, 'Password');

        return $this;
    }

    /**
     * Options to use for connecting to database
     *
     * @param string $options Options to use for connecting to database
     *
     * @return object $this
     */
    public function options($options)
    {
        $this->options = $this->validateString($options, 'Options');

        return $this;
    }

    /**
     * Connect to the database
     *
     * @return object $this
     */
    public function connect()
    {
        empty($this->driver) ? $this->driver() : $this->driver;
        empty($this->host) ? $this->host() : $this->host;
        if ($this->driver === 'sqlite') {
            $this->dsn = $this->driver.':'.$this->dbPath;
        } else {
            if ($this->unixSocket) {
                $this->dsn = $this->driver.':unix_socket='.$this->unixSocket.'dbname='.$this->dbName;
            } else {
                $port      = empty($this->port) ? null : ';port='.$this->port;
                $this->dsn = $this->driver.':host='.$this->host.$port.';dbname='.$this->dbName;
            }
        }
        $this->createPdo();

        return $this;
    }

    /**
     * Define the database driver to use.
     *
     * @param string $driver Optional. Defines the database driver to use. Default: `mysql`. Valid options: `mysql`,
     *                       `sqlite`.
     *
     * @return object $this
     * @throws RuntimeException if an invalid driver is used.
     */
    public function driver($driver = 'mysql')
    {
        if ($driver === 'mysql' || $driver === 'sqlite') {
            $this->driver = $driver;

            return $this;
        }

        throw new RuntimeException('This class only supports two drivers: `mysql` and `sqlite`');
    }

    /**
     * Define the database host name.
     *
     * @param string $host Optional. Defines the database host name driver to use. Default: `localhost`.
     *
     * @return object $this
     */
    public function host($host = 'localhost')
    {
        $this->host = $this->validateString($host, 'Database host name');

        return $this;
    }

    /**
     * Query string to run
     *
     * @param string $query Query string to run
     */
    public function query($query)
    {
        $this->action = $this->validateString($query, 'Query statement');
        $this->execute();
    }

    /**
     * Execute the prepared statement
     */
    public function execute()
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
    }

    /**
     * Select the columns for a query statement
     *
     * @param string ...$columns Comma-delimited list of columns to `SELECT`
     *
     * @return object $this
     */
    public function select(...$columns)
    {
        $columns      = implode(',', $columns);
        $columns      = $this->validateString($columns, '`SELECT` statement');
        $this->action .= ' SELECT '.$columns;

        return $this;
    }
    
    /**
     * UNION Combines the result from multiple query blocks into a single result set
     *
     * @param null
     *
     * @return object $this
     */
    public function union()
    {
        $this->action .= ' UNION ';

        return $this;
    }
    
    /**
     * UNION ALL Combines the result from multiple query blocks into a single result set
     *
     * @param null
     *
     * @return object $this
     */
    public function unionAll()
    {
        $this->action .= ' UNION ALL ';

        return $this;
    }
    
    /**
     * UNION DISTINCT Combines the result from multiple query blocks into a single result set
     *
     * @param null
     *
     * @return object $this
     */
    public function unionDistinct()
    {
        $this->action .= ' UNION DISTINCT ';

        return $this;
    }

    /**
     * Choose table to run the query on
     *
     * @param string $table Table to run the query on
     *
     * @return object $this
     */
    public function from($table)
    {
        $table        = $this->validateString($table, '`FROM` table name');
        $this->action .= ' FROM '.$table;

        return $this;
    }

    /**
     * `WHERE` statement in query
     *
     * @param string $where `WHERE` statement in query
     *
     * @return object $this
     */
    public function where($where)
    {
        $where        = $this->validateString($where, '`WHERE` statement');
        $this->action .= ' WHERE '.$where;

        return $this;
    }

    /**
     * `GROUP BY` statement in query
     *
     * @param string ...$groupBy Comma-delimited list of columns to group the query by
     *
     * @return object $this
     */
    public function groupBy(...$groupBy)
    {
        $groupBy      = implode(',', $groupBy);
        $groupBy      = $this->validateString($groupBy, '`GROUP BY` statement');
        $this->action .= ' GROUP BY '.$groupBy;

        return $this;
    }

    /**
     * `ORDER BY` statement in query
     *
     * @param array|string $orderBy Array for ordering by multiple columns or string for ordering by a single column
     *
     * @return object $this
     */
    public function orderBy($orderBy)
    {
        $orderBy      = is_array($orderBy) ? implode(',', $orderBy) : $orderBy;
        $orderBy      = $this->validateString($orderBy, '`ORDER BY` statement');
        $this->action .= ' ORDER BY '.$orderBy;

        return $this;
    }

    /**
     * Limit the number of results returned
     *
     * @param string $limit String statement for how to limit the query. Usually an `int` unless you are also
     *                      specifying an offset
     *
     * @return object $this
     */
    public function limit($limit)
    {
        $limit        = $this->validateString($limit, '`LIMIT` statement');
        $this->action .= ' LIMIT '.$limit;

        return $this;
    }

    /**
     * Bind values in a prepared statement
     *
     * @param array ...$bindArray Array(s) of values to bind to prepared statement
     *
     * @return object $this
     */
    public function bind(...$bindArray)
    {
        foreach ($bindArray as $key => $value) {
            $this->bind[$key] = $value;
        }

        return $this;
    }

    /**
     * Fetch the query result(s)
     *
     * @param int $type Type of results to return. Default: `\PDO::FETCH_ASSOC`.
     *
     * @return mixed Query results in the desired type
     */
    public function fetch($type = PDO::FETCH_ASSOC)
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
        $this->result = $db->fetch($type);

        return $this->result;
    }

    /**
     * Fetch all of the query result(s)
     *
     * @param int $type Type of results to return. Default: `\PDO::FETCH_ASSOC`.
     *
     * @return mixed Query results in the desired type
     */
    public function fetchAll($type = PDO::FETCH_ASSOC)
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
        $this->result = $db->fetchAll($type);

        return $this->result;
    }

    /**
     * Table to insert the data into
     *
     * @param string $table Name of table
     *
     * @return object $this
     */
    public function insert($table)
    {
        $table        = $this->validateString($table, '`INSERT` table name');
        $this->action .= ' INSERT INTO '.$table;

        return $this;
    }

    /**
     * File to dump results to
     *
     * @param string $file File name/path to save the results to
     *
     * @return object $this
     */
    public function intoOutfile($file)
    {
        $file         = $this->validateString($file, '`INTO OUTFILE` file name');
        $this->action .= ' INTO OUTFILE '.$file;

        return $this;
    }

    /**
     * Columns to use in query
     *
     * @param string ...$columns Comma-delimited list of columns
     *
     * @return object $this
     */
    public function columns(...$columns)
    {
        $columns      = implode(',', $columns);
        $columns      = $this->validateString($columns, 'Column statement');
        $this->action .= ' ('.$columns.')';

        return $this;
    }

    /**
     * Values to place into table
     *
     * @param mixed ...$values Comma-delimited list of values
     *
     * @return object $this
     */
    public function values(...$values)
    {
        $values       = implode(',', $values);
        $values       = $this->validateString($values, 'Values statement', true);
        $this->action .= ' VALUES ('.$values.')';

        return $this;
    }

    /**
     * `ON DUPLICATE KEY UPDATE` statement in query
     *
     * @param string $params Parameters to use when updating a duplicate key
     *
     * @return object $this
     */
    public function onDuplicateKeyUpdate($params)
    {
        $params       = $this->validateString($params, '`ON DUPLICATE KEY UPDATE` parameters');
        $this->action .= ' ON DUPLICATE KEY UPDATE '.$params;

        return $this;
    }

    /**
     * Update values in a table
     *
     * @param string $table Table to update
     *
     * @return object $this
     */
    public function update($table)
    {
        $table        = $this->validateString($table, '`UPDATE` table name');
        $this->action .= ' UPDATE '.$table;

        return $this;
    }

    /**
     * Values to set columns to
     *
     * @param mixed ...$columns Comma-delimited list of column values
     *
     * @return object $this
     */
    public function set(...$columns)
    {
        $columns      = implode(',', $columns);
        $columns      = $this->validateString($columns, '`SET` columns');
        $this->action .= ' SET '.$columns;

        return $this;
    }

    /**
     * Delete row(s) from table
     *
     * @param string $table Table name to delete row(s) from
     *
     * @return object $this
     */
    public function delete($table)
    {
        $table        = $this->validateString($table, '`DELETE FROM` table name');
        $this->action .= ' DELETE FROM '.$table;

        return $this;
    }

    /**
     * Load data from a file into the database
     *
     * @param string $file File name/path to load into the database
     *
     * @return object $this
     */
    public function loadDataInfile($file)
    {
        $file         = $this->validateString($file, '`LOAD DATA INFILE` file name');
        $this->action .= ' LOAD DATA INFILE '.$file;

        return $this;
    }

    /**
     * Character set to use for the query
     *
     * @param string $characterSet Character set to use for the query
     *
     * @return object $this
     */
    public function characterSet($characterSet)
    {
        $this->validateString($characterSet, 'Character set');
        $this->action .= ' CHARACTER SET '.$characterSet;

        return $this;
    }

    /**
     * Tell database how fields are separated in the file being loaded
     *
     * @param string $fieldsTerminatedBy String used to terminate fields
     *
     * @return object $this
     */
    public function fieldsTerminatedBy($fieldsTerminatedBy)
    {
        $this->validateString($fieldsTerminatedBy, '`FIELDS TERMINATED BY` string');
        $this->action .= ' FIELDS TERMINATED BY '.$fieldsTerminatedBy;

        return $this;
    }

    /**
     * Tell database how lines are terminated in the file being loaded
     *
     * @param string $linesTerminatedBy String used to terminate a line
     *
     * @return object $this
     */
    public function linesTerminatedBy($linesTerminatedBy)
    {
        $this->validateString($linesTerminatedBy, '`LINES TERMINATED BY` string');
        $this->action .= ' LINES TERMINATED BY '.$linesTerminatedBy;

        return $this;
    }

    /**
     * Enclosed by string in query
     *
     * @param string $enclosedBy String used to enclose fields
     * @param bool   $optionally Whether or not the `ENCLOSED BY` string is optional or not for the field
     *
     * @return object $this
     */
    public function enclosedBy($enclosedBy, $optionally = false)
    {
        $optionally = $optionally ? ' OPTIONALLY' : null;
        $this->validateString($enclosedBy, '`ENCLOSED BY` string');
        $this->action .= $optionally.' ENCLOSED BY '.$enclosedBy;

        return $this;
    }

    /**
     * String used to escape fields
     *
     * @param string $escapedBy String used to escape fields
     *
     * @return object $this
     */
    public function escapedBy($escapedBy)
    {
        $this->validateString($escapedBy, '`ESCAPED BY` string');
        $this->action .= ' ESCAPED BY '.$escapedBy;

        return $this;
    }

    /**
     * Prefix string used to start each line of a file you want to ignore
     *
     * @param string $startingBy Prefix string used to start a new line you want to ignore
     *
     * @return object $this
     */
    public function startingBy($startingBy)
    {
        $this->validateString($startingBy, '`STARTING BY` string');
        $this->action .= ' STARTING BY '.$startingBy;

        return $this;
    }

    /**
     * Number of lines in the file to ignore (for example, hide first line that is a header)
     *
     * @param int $lines Number of lines to ignore
     *
     * @return object $this
     */
    public function ignore($lines)
    {
        $this->validateInteger($lines, '`IGNORE LINES` string');
        $this->action .= ' IGNORE '.$lines.' LINES';

        return $this;
    }

    /**
     * Replace an existing row in the table
     *
     * @param string $table Table to replace the row in
     *
     * @return object $this
     */
    public function replace($table)
    {
        $table        = $this->validateString($table, '`REPLACE INTO` table name');
        $this->action .= ' REPLACE INTO '.$table;

        return $this;
    }

    /**
     * Left join table(s)
     *
     * @param string $tables Table(s) to left join
     *
     * @return object $this
     */
    public function leftJoin($tables)
    {
        $tables       = $this->validateString($tables, '`LEFT JOIN` table names');
        $this->action .= ' LEFT JOIN '.$tables;

        return $this;
    }

    /**
     * Inner join table(s)
     *
     * @param string $tables Table(s) to inner join
     *
     * @return object $this
     */
    public function innerJoin($tables)
    {
        $tables       = $this->validateString($tables, '`INNER JOIN` table names');
        $this->action .= ' INNER JOIN '.$tables;

        return $this;
    }

    /**
     * Values to join tables on
     *
     * @param string $tableValues Values used to join tables together
     *
     * @return object $this
     */
    public function on($tableValues)
    {
        $tableValues  = $this->validateString($tableValues, '`ON` table names');
        $this->action .= ' ON ('.$tableValues.')';

        return $this;
    }

    /**
     * Truncate a table
     *
     * @param string $table Table to truncate
     */
    public function truncate($table)
    {
        $table        = $this->validateString($table, '`TRUNCATE` table name');
        $this->action .= 'TRUNCATE TABLE '.$table;
        $this->execute();
    }

    /**
     * Dump the database to file to serve as a backup
     *
     * @param string $file File name/path to save the database to
     */
    public function backup($file)
    {
        $file = $this->validateString($file, 'Backup file name');
        $this->mysqldump($file);
    }

    /**
     * Dump the database to a file
     *
     * @param string $file File name/path to save the database to
     */
    public function mysqldump($file)
    {
        $file = $this->validateString($file, '`mysqldump` file name');
        exec('mysqldump --user='.$this->username.' --password='.$this->password.' --host='.$this->host.' '.$this->dbName.' > '.$file);
    }

    /**
     * Print example SQL queries to the screen
     */
    public function sqlRef()
    {
        echo '<br/><pre>';
        echo "SELECT:\nSELECT `column1`, `column2` FROM `table` WHERE `column1` = 'value' GROUP BY `column1` ORDER BY `column2` LIMIT 2\n\n";
        echo "INSERT:\nINSERT INTO `table` (`column1`, `column2`) VALUES (`value1`, `value2`) ON DUPLICATE KEY UPDATE `column1` = 'value'\n\n";
        echo "REPLACE:\nREPLACE INTO `table` (`column1`, `column2`) VALUES ('value1', 'value2')\n\n";
        echo "DELETE:\nDELETE FROM `table` WHERE `column` = 'value' ORDER BY `column` LIMIT 2\n\n";
        echo "UPDATE:\nUPDATE `table` SET `column1` = 'value1', `column2` = 'value2' WHERE `column1` = 'value1' ORDER BY `column2` LIMIT 2\n\n";
        echo "LEFT JOINT:\nSELECT `column` FROM `table1` LEFT JOIN (`table2`, `table3`) ON (`table2`.`column` = `table1`.`column` AND `table3`.`column` = `table1`.`column`)";
        echo '</pre><br/>';
    }

    /**
     * Validate the string
     *
     * @param string $string    String to validate
     * @param string $message   Message to return if an exception is thrown
     * @param bool   $allowNull Whether or not to allow a `null` string. Default: `false`
     *
     * @return string|null String if valid, `null` otherwise
     *
     * @throws RuntimeException if `$string` is not a string
     */
    private function validateString($string, $message, $allowNull = false)
    {
        if (is_string($string)) {
            return $string;
        }

        if ($allowNull === true) {
            return null;
        }

        throw new RuntimeException("$message must be a string.");
    }

    /**
     * Validate the integer
     *
     * @param int    $int       Integer to validate
     * @param string $message   Message to return if an exception is thrown
     * @param bool   $allowNull Whether or not to allow a `null` integer. Default: `false`
     *
     * @return string|null Integer if valid, `null` otherwise
     *
     * @throws RuntimeException if `$int` is not an integer
     */
    private function validateInteger($int, $message, $allowNull = false)
    {
        if (is_int($int)) {
            return $int;
        }

        if ($allowNull === true) {
            return null;
        }

        throw new RuntimeException("$message must be an integer.");
    }

    /**
     * Create a PDO database object
     */
    private function createPdo()
    {
        if ($this->username && $this->password && $this->options) {
            $this->db = new PDO($this->dsn, $this->username, $this->password, $this->options);
        } elseif ($this->username && $this->password) {
            $this->db = new PDO($this->dsn, $this->username, $this->password);
        } else {
            $this->db = new PDO($this->dsn);
        }
    }

}
