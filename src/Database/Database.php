<?php
/**
  * Database class
  *
  * Right now this thing is not too useful but I hope to change that some time soon-ish.
  *
  * @link http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/ Based on this post
  * @author  Jared Howland <database@jaredhowland.com>
  * @version 2016-10-27
  * @since 2016-10-27
  */

namespace Database;

/**
 * Database class
 *

 * @param string $dbType Type of database to connect to. Currently only `mysql` is supported
 * @return null
 */
class Database
{
    /** @var string Database host name */
    private $host;
    /** @var string Database username */
    private $user;
    /** @var string Database password */
    private $pass;
    /** @var string Database name */
    private $dbname;
    /** @var object Database handle */
    private $dbh;
    /** @var string Error message */
    private $error;
    /** @var string Database statement */
    private $stmt;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database username
     * @param string $password Database password
     * @param string $database Name of database to connect to
     * @param string $dbType Currently only `mysql` is supported. Will move to a more flexible model eventually.
     * @return null
     */
    public function __construct($host, $user, $password, $database, $dbType = 'mysql')
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $database;
        // Set Data Source Name (DSN)
        if ($dbType === 'mysql') {
            $dsn = 'mysql:host=' . $host . ';dbname=' . $database;
        } else {
            throw new \Exception('Currently, MySQL (`msyql`) is the only supported database.');
        }
        // Set options
        $options = array(
            \PDO::ATTR_PERSISTENT         => true,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8'
        );
        // Create a new PDO instanace
        try {
            $this->dbh = new \PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * Destructor
     *
     * @param null
     * @return null
     */
    public function __destruct()
    {
        $this->dbh = null;
    }

    /**
     * Prepare the SQL query
     *
     * @param string $query Query to run
     * @return null
     */
    public function query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    /**
     * Bind parameters to query
     *
     * @param string $param Named parameter to bind
     * @param string $value Value to bind
     * @return null
     */
    public function bind($param, $value, $type = null)
    {
        if (empty($type)) {
            switch(true) {
                case is_int($value):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Bind multiple parameters to a query using an array
     *
     * @param array $paramArray Array of parameters ('key' => $value)
     * @return null
     */
    public function bindArray($paramArray)
    {
        foreach($paramArray as $param => $value) {
            $this->bind($param, $value);
        }
    }

    /**
     * Execute the query
     *
     * @param null
     * @return null
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * Fetch all the results as an associative array
     *
     * @param null
     * @return
     */
    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single result
     *
     * @param null
     * @return null
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Count the number of rows returned by the query
     *
     * @param null
     * @return null
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get the last inserted `id`
     *
     * @param null
     * @return null
     */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Begin a transaction
     *
     * @param null
     * @return null
     */
    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @param null
     * @return null
     */
    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    /**
     * Cancel a transaction
     *
     * @param null
     * @return null
     */
    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }

    /**
     * Dump debug information about the query
     *
     * @param null
     * @return null
     */
    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

    /**
     * Truncate the table
     *
     * @param string $table Name of table to truncate
     * @return null
     */
    public function truncate($table)
    {
        $this->query("TRUNCATE TABLE $table");
        $this->execute();
    }

    /**
     * Disable foreign key constraints
     *
     * @param null
     * @return null
     */
    public function disableConstraints()
    {
        $this->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->execute();
    }

    /**
     * Enable foreign key constraints
     *
     * @param null
     * @return null
     */
    public function enableConstraints()
    {
        $this->query("SET FOREIGN_KEY_CHECKS = 1");
        $this->execute();
    }
}
