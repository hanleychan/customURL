<?php
require_once('DatabaseObject.php');
class Admin extends DatabaseObject {
    public $id;
    public $username;
    public $password;

    protected static $table_name = "admins";
    protected static $db_fields = array('id', 'username', 'password');

    /**
     * Returns an admin if object if it the username/password combination exists
     */
    public static function authenticate($db, $username, $password) {
        // check if username exists
        $sql = "SELECT * FROM admins WHERE username = ? LIMIT 1";
        $paramArray = array($username);
        $result = self::findBySQL($db, $sql, $paramArray);

        if($result) {
            // check if password is correct 
            $hash = $result[0]->password;
            if(password_verify($password, $hash)) { 
                return $result[0];
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
}
?>
