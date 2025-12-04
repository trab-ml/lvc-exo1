<?php
namespace App\Config;

use \PDO, \PDOException;

loadEnv(__DIR__ . "/../../.env");

class DatabaseConnection
{
    private static ?DatabaseConnection $instance;
    private static ?PDO $conn;

    private function __construct() { /** Only one instance of DB needed ! */ }

    public static function get_instance(): DatabaseConnection
    {
        if (!isset(self::$instance)) {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=" . $_ENV['DB_CHARSET'];
            try {
                self::$instance = new DatabaseConnection();
                self::$conn = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            } catch (PDOException $ex) {
                var_dump($_ENV);
                echo "<p class='col-red'>Could not connect to the DB " . $ex->getMessage() . "</p>";
            }
        }
        return self::$instance;
    }

    public static function get_db_conn(): PDO {
        if (!isset(self::$conn)) {
            self::get_instance();
        }
        return self::$conn;
    }

    public static function close_connexion(): void {
        self::$conn = null;
        self::$instance = null;
    }
}
