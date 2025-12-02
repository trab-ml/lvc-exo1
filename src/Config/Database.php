<?php
namespace App\Config;

use \PDO, \PDOException;

loadEnv(__DIR__ . "/../../.env");

class Database
{
    private static ?PDO $instance;

    private function __construct() {}

    public static function get_instance(): PDO
    {
        if (!isset(self::$instance)) {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=" . $_ENV['DB_CHARSET'];
            try {
                self::$instance = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            } catch (PDOException $ex) {
                var_dump($_ENV);
                echo "<p class='col-red'>Could not connect to the DB " . $ex->getMessage() . "</p>";
            }
        }
        return self::$instance;
    }
}
