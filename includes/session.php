<?php
class Session {
    private $loggedIn = false;
    public $adminID;

    public function __construct() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkLogin();
    }

    public function login($admin) {
        if($admin) {
            $this->adminID = $_SESSION["url"]["adminID"] = $admin->id;
            $this->loggedIn = true;
        }
    }

    public function logout() {
        $this->loggedIn = false;
        unset($this->adminID);
        unset($_SESSION["url"]["adminID"]);
    }

    public function isLoggedIn() {
        return $this->loggedIn;
    }

    private function checkLogin() {
        if(isset($_SESSION["url"]["adminID"])) {
            $this->loggedIn = true;
            $this->adminID = $_SESSION["url"]["adminID"];
        }
        else {
            $this->loggedIn = false;
            unset($this->adminID);
        }
    }
}

?>
