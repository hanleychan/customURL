<?php
class Admin extends DatabaseObject {
    public $id;
    public $username;
    public $password;

    protected static $table_name = "admins";
    protected static $db_fields = array('id', 'username', 'password');

    public static function authenticate($db, $username, $password) {
        $sql = "SELECT * FROM admins WHERE username = ? AND password = ? LIMIT 1";
        $paramArray = array($username, $password);

        $result = self::findBySQL($db, $sql, $paramArray);

        if($result) {
            return $result[0];
        }
        else {
            return false;
        }
    }
}
?>
