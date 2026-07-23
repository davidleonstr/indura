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
    private string $host;

    /**
     * @var string Database name.
     */
    private string $dbname;

    /**
     * @var string Username for connection.
     */
    private string $username;

    /**
     * @var string Password for connection.
     */
    private string $password;

    /**
     * @var int Port for connection.
     */
    private int $port;

    /**
     * @var string Charset for connection.
     */
    private string $charset;

    /**
     * PostgreSQL class constructor.
     *
     * @param string $host Database host.
     * @param string $dbname Database name.
     * @param string $username Username for connection.
     * @param string $password Password for connection.
     * @param int $port Port for connection (default: 5432).
     * @param string $charset Character set (default: UTF8).
     */
    public function __construct(
        string $host, 
        string $dbname, 
        string $username, 
        string $password, 
        int $port = 5432, 
        string $charset = 'UTF8'
    ) {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->charset = $charset;
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
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};options='--client_encoding={$this->charset}'";

        try {
            $pdo = new PDO(
                dsn: $dsn,
                username: $this->username,
                password: $this->password
            );
            
            $pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(attribute: PDO::ATTR_EMULATE_PREPARES, value: false);
            $pdo->setAttribute(attribute: PDO::ATTR_DEFAULT_FETCH_MODE, value: PDO::FETCH_ASSOC);

            return $pdo;
        } catch (PDOException $e) {
            return $e;
        }  
    }
}