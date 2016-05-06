<?php
require_once("../classes/MySQLDatabase.php");
require_once("../classes/Admin.php");
require_once("../classes/DatabaseObject.php");

$host = 'localhost';
$dbName = 'url';
$port = 3306;
$user = 'hanley';
$password = 'apple';

$db = new MySQLDatabase($host, $dbName, $port, $user, $password);
$username = "admin";
$password = password_hash("secretPassword", PASSWORD_BCRYPT);

$admin = new Admin($db);
$admin->username = $username;
$admin->password = $password;
$admin->save();
echo "Admin created";
echo $password;

?>
