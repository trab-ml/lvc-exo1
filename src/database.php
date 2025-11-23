<?php
// TODO: VARIABILIZE
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cashier_db";
$charset = "utf8";
$dsn = "mysql:host=$db_server;dbname=$db_name;charset=$charset";

try {
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $ex) {
    echo "<p class='col-red'>Could not connect to the DB " . $ex->getMessage() . "</p>";
}
