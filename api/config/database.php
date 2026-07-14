<?php

class Database
{
    // specify your own database credentials
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;
    public $err_msg;
    public $err_code;

    // constructor
    public function __construct($config = [])
    {
        $this->host = key_exists('hostname', $config) ? $config['hostname'] : '127.0.0.1';
        $this->db_name = key_exists('dbname', $config) ? $config['dbname'] : '';
        $this->username = key_exists('username', $config) ? $config['username'] : '';
        $this->password = key_exists('password', $config) ? $config['password'] : '';

        $this->conn = null;
        $this->err_msg = null;
        $this->err_code = 0;
    }

    // get the database connection
    public function getConnection()
    {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            $this->err_code = $exception->getCode();
            $this->err_msg = "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}