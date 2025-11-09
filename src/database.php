<?php
class Database
{
    private mysqli $conn;

    public function __construct(
        private string $host,
        private string $name,
        private string $user,
        private string $password
    ){
    }

    public function getConnection(): mysqli
    {
        if (!isset($this->conn)) {
            $this->conn = new mysqli($this->host, $this->user, $this->password, $this->name);
            if ($this->conn->connect_errno) {
                throw new RuntimeException("DB connect error: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8");
        }
        return $this->conn;
    }
}