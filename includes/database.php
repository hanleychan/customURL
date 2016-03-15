<?php
require_once("db_config.php");

class MySQLDatabase {
    private $connection;

    /**
     * Setup database
     */
    public function __construct() {
        $this->openConnection();
    }

    /**
     * Opens the the connection to the MySql database
     */
    public function openConnection() {
        try {
            $this->connection = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT, DB_USER, DB_PASS );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->exec("SET NAMES 'utf8'");
        }
        catch(Exception $e) {
            die("Database connection failed");
        }
    }

    /**
     * Closes the database connection
     */
    public function closeConnection() {
        $this->connection = null;
    }

    /**
     * Executes a query and returns the result
     */
    public function query($sql) {
        try {
            $results = $this->connection->query($sql);
        }
        catch(Exception $e) {
            die("Database query failed.");
        }

        return $results;
    }

    /**
     * Prepares a sql statement for execution
     */
    public function prepare($sql) {
        try {
            $results = $this->connection->prepare($sql);
        }
        catch(Exception $e) {
            die("Database query failed");
        }

        return $results;
    }

    /**
     * Returns the id of the last inserted row
     */
    public function lastInsertId() {
        try {
            $lastInsertId = $this->connection->lastInsertId();
        }
        catch(Exception $e) {
            die("Error retrieving from database.");
        }

        return $lastInsertId;
    }
}

?>
