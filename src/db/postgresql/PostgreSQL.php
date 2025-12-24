<?php
namespace indura\db\postgresql;

use PDO;
use PDOException;

/**
 * Class for handling PostgreSQL database connection using PDO.
 */
class PostgreSQL {
    /**
     * @var string Database host.
     */
    private $host;

    /**
     * @var string Database name.
     */
    private $dbname;

    /**
     * @var string Username for connection.
     */
    private $username;

    /**
     * @var string Password for connection.
     */
    private $password;

    /**
     * @var int Port for connection.
     */
    private $port;

    /**
     * SQLHandler class constructor.
     *
     * @param string $host Database host.
     * @param string $dbname Database name.
     * @param string $username Username for connection.
     * @param string $password Password for connection.
     * @param int $port Port for connection.
     */
    public function __construct(string $host, string $dbname, string $username, string $password, int $port) {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }

    /**
     * Gets a PDO connection to the database.
     *
     * This function attempts to establish a database connection using PDO.
     * If the connection is successful, the PDO object is returned. If an exception occurs,
     * the PDOException object is returned.
     *
     * @return PDO|PDOException PDO object for database connection, or PDOException on error.
     */
    public function getConnection(): PDO|PDOException {
        $dsn = "pgsql:host=$this->host;port=$this->port;dbname=$this->dbname;user=$this->username;password=$this->password";

        try {
            $pdo = new PDO(dsn: $dsn);
            
            $pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(attribute: PDO::ATTR_EMULATE_PREPARES, value: false);

            return $pdo;
        } catch (PDOException $e) {
            return $e;
        }  
    }
}