<?php
/**
 * Database wrapper for PDO
 *
 * @author  Jared Howland <database@jaredhowland.com>
 * @version 2017-03-29
 * @since   2017-03-16
 */

namespace Database;

use Exception;

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
     * @throws Exception if an invalid port number is used.
     *
     * @return $this
     */
    public function port($port = null)
    {
        if (($port >= 0 AND $port <= 1024) OR is_null($port)) {
            $this->port = $port;

            return $this;
        } else {
            throw new Exception("Invalid port number. Must be `null` or an integer ranging between 0 and inclusive.");
        }
    }

    /**
     * Define the database name to use.
     *
     * @param string $dbName Defines the database name to use.
     *
     * @throws Exception if `$dbName` is not a string.
     *
     * @return $this
     */
    public function dbName($dbName)
    {
        $this->dbName = $this->validateString($dbName, 'Database name');

        return $this;
    }

    private function validateString($string, $message, $allowNull = false)
    {
        if (is_string($string)) {
            return $string;
        } elseif ($allowNull === true) {
            return null;
        } else {
            throw new Exception("$message must be a string.");
        }
    }

    /**
     * Define the Unix socket to use.
     *
     * @param string $unixSocket Optional. Defines the Unix socket to use. Default: `null`.
     *
     * @return $this
     */
    public function unixSocket($unixSocket = null)
    {
        $this->unixSocket = $this->validateString($unixSocket, 'Unix socket');

        return $this;
    }

    public function charset($charset = 'utf8')
    {
        $this->charset = $this->validateString($charset, 'charset');

        return $this;
    }

    public function dbPath($dbPath)
    {
        if (file_exists($dbPath) OR $dbPath == ':memory:') {
            $this->dbPath = $dbPath;

            return $this;
        } else {
            throw new Exception("You have entered an invalid path (`$dbPath`) to the database file.");
        }
    }

    public function username($username)
    {
        $this->username = $this->validateString($username, 'Username');

        return $this;
    }

    public function password($password)
    {
        $this->password = $this->validateString($password, 'Password');

        return $this;
    }

    public function options($options)
    {
        $this->options = $this->validateString($options, 'Options');

        return $this;
    }

    public function connect()
    {
        empty($this->driver) ? $this->driver() : $this->driver;
        empty($this->host) ? $this->host() : $this->host;
        if ($this->driver == 'sqlite') {
            $this->dsn = $this->driver.':'.$this->dbPath;
        } else {
            if ($this->unixSocket) {
                $this->dsn = $this->driver.':unix_socket='.$this->unixSocket.'dbname='.$this->dbName;
            } else {
                $port = empty($this->port) ? null : ';port='.$this->port;
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
     * @throws Exception if an invalid driver is used.
     *
     * @return $this
     */
    public function driver($driver = 'mysql')
    {
        if ($driver == 'mysql' OR $driver == 'sqlite') {
            $this->driver = $driver;

            return $this;
        } else {
            throw new Exception("This class only supports two drivers: `mysql` and `sqlite`");
        }
    }

    /**
     * Define the database host name.
     *
     * @param string $host Optional. Defines the database host name driver to use. Default: `localhost`.
     *
     * @throws Exception if `$host` is not a string.
     *
     * @return $this
     */
    public function host($host = 'localhost')
    {
        $this->host = $this->validateString($host, 'Database host name');

        return $this;
    }

    private function createPdo()
    {
        if ($this->username AND $this->password AND $this->options) {
            $this->db = new \PDO($this->dsn, $this->username, $this->password, $this - options);
        } else {
            if ($this->username AND $this->password) {
                $this->db = new \PDO($this->dsn, $this->username, $this->password);
            } else {
                $this->db = new \PDO($this->dsn);
            }
        }
    }

    public function query($query)
    {
        $this->action = $this->validateString($query, 'Query statement');
        $this->execute();
    }

    public function execute()
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
    }

    public function select(...$columns)
    {
        $columns = implode(',', $columns);
        $columns = $this->validateString($columns, '`SELECT` statement');
        $this->action .= ' SELECT '.$columns;

        return $this;
    }

    public function from($table)
    {
        $table = $this->validateString($table, '`FROM` table name');
        $this->action .= ' FROM '.$table;

        return $this;
    }

    public function where($where)
    {
        $where = $this->validateString($where, '`WHERE` statement');
        $this->action .= ' WHERE '.$where;

        return $this;
    }

    public function groupBy(...$groupBy)
    {
        $groupBy = implode(',', $groupBy);
        $groupBy = $this->validateString($groupBy, '`GROUP BY` statement');
        $this->action .= ' GROUP BY '.$groupBy;

        return $this;
    }

    public function orderBy($orderBy)
    {
        $orderBy = is_array($orderBy) ? implode(',', $orderBy) : $orderBy;
        $orderBy = $this->validateString($orderBy, '`ORDER BY` statement');
        $this->action .= ' ORDER BY '.$orderBy;

        return $this;
    }

    public function limit($limit)
    {
        $limit = $this->validateString($limit, '`LIMIT` statement');
        $this->action .= ' LIMIT '.$limit;

        return $this;
    }

    public function bind(...$bindArray)
    {
        foreach ($bindArray as $key => $value) {
            $this->bind[$key] = $value;
        }

        return $this;
    }

    public function fetch($type = \PDO::FETCH_ASSOC)
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
        $this->result = $db->fetch($type);

        return $this->result;
    }

    public function fetchAll($type = \PDO::FETCH_ASSOC)
    {
        $db = $this->db->prepare($this->action);
        $db->execute($this->bind[0]);
        $this->action = null;
        $this->result = $db->fetchAll($type);

        return $this->result;
    }

    public function insert($table)
    {
        $table = $this->validateString($table, '`INSERT` table name');
        $this->action .= ' INSERT INTO '.$table;

        return $this;
    }

    public function intoOutfile($file)
    {
        $file = $this->validateString($file, '`INTO OUTFILE` file name');
        $this->action .= ' INTO OUTFILE '.$file;

        return $this;
    }

    public function columns(...$columns)
    {
        $columns = implode(',', $columns);
        $columns = $this->validateString($columns, 'Column statement');
        $this->action .= ' ('.$columns.')';

        return $this;
    }

    public function values(...$values)
    {
        $values = implode(',', $values);
        $values = $this->validateString($values, 'Values statement', true);
        $this->action .= ' VALUES ('.$values.')';

        return $this;
    }

    public function onDuplicateKeyUpdate($params)
    {
        $params = $this->validateString($params, '`ON DUPLICATE KEY UPDATE` parameters');
        $this->action .= ' ON DUPLICATE KEY UPDATE '.$params;

        return $this;
    }

    public function update($table)
    {
        $table = $this->validateString($table, '`UPDATE` table name');
        $this->action .= ' UPDATE '.$table;

        return $this;
    }

    public function set(...$columns)
    {
        $columns = implode(',', $columns);
        $columns = $this->validateString($columns, '`SET` columns');
        $this->action .= ' SET '.$columns;

        return $this;
    }

    public function delete($table)
    {
        $table = $this->validateString($table, '`DELETE FROM` table name');
        $this->action .= ' DELETE FROM '.$table;

        return $this;
    }

    public function loadDataInfile($file)
    {
        $file = $this->validateString($file, '`LOAD DATA INFILE` file name');
        $this->action .= ' LOAD DATA INFILE '.$file;

        return $this;
    }

    public function characterSet($characterSet)
    {
        $this->validateString($characterSet, 'Character set');
        $this->action .= ' CHARACTER SET '.$characterSet;

        return $this;
    }

    public function fieldsTerminatedBy($fieldsTerminatedBy)
    {
        $this->action .= ' FIELDS TERMINATED BY '.$fieldsTerminatedBy;

        return $this;
    }

    public function linesTerminatedBy($linesTerminatedBy)
    {
        $this->action .= ' LINES TERMINATED BY '.$linesTerminatedBy;

        return $this;
    }

    public function enclosedBy($enclosedBy, $optionally = false)
    {
        $optionally = $optionally ? ' OPTIONALLY' : null;
        $this->action .= $optionally.' ENCLOSED BY '.$enclosedBy;

        return $this;
    }

    public function escapedBy($escapedBy)
    {
        $this->action .= ' ESCAPED BY '.$escapedBy;

        return $this;
    }

    // int

    public function startingBy($startingBy)
    {
        $this->action .= ' STARTING BY '.$startingBy;

        return $this;
    }

    public function ignore($lines)
    {
        $this->action .= ' IGNORE '.$lines.' LINES';

        return $this;
    }

    public function replace($table)
    {
        $table = $this->validateString($table, '`REPLACE INTO` table name');
        $this->action .= ' REPLACE INTO '.$table;

        return $this;
    }

    public function leftJoin($tables)
    {
        $tables = $this->validateString($tables, '`LEFT JOIN` table names');
        $this->action .= ' LEFT JOIN ('.$tables.')';

        return $this;
    }

    public function on($tables)
    {
        $tables = $this->validateString($tables, '`ON` table names');
        $this->action .= ' ON ('.$tables.')';

        return $this;
    }

    public function truncate($table)
    {
        $table = $this->validateString($table, '`TRUNCATE` table name');
        $this->action .= 'TRUNCATE TABLE '.$table;

        return $this;
    }

    public function backup($file)
    {
        $file = $this->validateString($file, 'Backup file name');
        $this->mysqldump($file);
    }

    public function mysqldump($file)
    {
        $file = $this->validateString($file, '`mysqldump` file name');
        exec('mysqldump --user='.$this->username.' --password='.$this->password.' --host='.$this->host.' '.$this->dbName.' > '.$file);
    }

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

}
