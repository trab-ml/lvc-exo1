<?php
namespace App\Interfaces;
use PDO;

interface DatabaseInterface {
    public function get_db_conn(): PDO;
    public function close_connexion(): void;
}
