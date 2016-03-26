<?php
require_once("includes/initialize.php");

$db = new MySQLDatabase();
$username = "admin";
$password = password_hash("secretpassword", PASSWORD_BCRYPT);

$admin = new Admin($db);
$admin->username = $username;
$admin->password = $password;
$admin->save();
echo "Admin created";
echo $password;

?>
