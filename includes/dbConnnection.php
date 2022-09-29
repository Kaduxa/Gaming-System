<?php
class DBConnection{

    private const DB_HOST   = "35.176.198.15";
    private const DB_USER   = "dev_gamingSystem";
    private const DB_PASS   = "iE)Fxh9jXc6%bPwk-w8l1RkWP)n%OSz1pshPX-j)6WOe%o*dsy(@)E^OR3CTx3Jl";
    private const DB_NAME   = "dev_gamingSystem";

    private $conn;
    private $isOpen;

    function __construct(){
      $this->conn     = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASS, self::DB_NAME);
      $this->conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
      $this->isOpen   = true;
    }

    function __destruct(){
      $this->conn->close();
      $this->isOpen   = false;
    }

    function getConnection(){
      return $this->conn;
    }
}
