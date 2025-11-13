<?php

// Database Connection Class
class Database
{
    // Database connection instance
    private mysqli $conn;
    // Constructor to initialize database connection parameters
    public function __construct(
        // Database host
        private string $host,
        private string $name,
        private string $user,
        private string $password
    ){
    }

    public function getConnection(): mysqli
    {
        // Create a new database connection if not already established
        if (!isset($this->conn)) {
            // Establish the database connection
            $this->conn = new mysqli($this->host, $this->user, $this->password, $this->name);
            // Check for connection errors
            if ($this->conn->connect_errno) {
                // Throw an exception if connection fails
                throw new RuntimeException("DB connect error: " . $this->conn->connect_error);
            }
            // Set the character set to UTF-8
            $this->conn->set_charset("utf8");
        }
        // Return the database connection instance
        return $this->conn;
    }
}