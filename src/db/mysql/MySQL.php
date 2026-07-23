<?php
namespace indura\db\mysql;

use PDO;
use PDOException;

/**
 * Class for handling MySQL database connection using PDO.
 */
class MySQL {
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
     * MySQL class constructor.
     *
     * @param string $host Database host.
     * @param string $dbname Database name.
     * @param string $username Username for connection.
     * @param string $password Password for connection.
     * @param int $port Port for connection (default: 3306).
     * @param string $charset Character set (default: utf8mb4).
     */
    public function __construct(
        string $host, 
        string $dbname, 
        string $username, 
        string $password, 
        int $port = 3306, 
        string $charset = 'utf8mb4'
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
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

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