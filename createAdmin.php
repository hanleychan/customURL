<?php
require_once("includes/initialize.php");

$db = new MySQLDatabase();
$username = "admin";
$password = "secretpassword";

$admin = new Admin($db);
$admin->username = $username;
$admin->password = $password;
$admin->save();
echo "Admin created";

?>
