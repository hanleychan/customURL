<?php
require_once("initialize.php");

class Session {
    private $loggedIn = false;
    private $previousPage;
    public $adminID;


    public function __construct() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(!isset($_SESSION["url"]["prevPage"])) {
            $this->previousPage = BASE_URL;
        }
        else {
            $this->previousPage = $_SESSION["url"]["prevPage"];
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

    public function updatePage($page='home') {
        $this->previousPage = $_SESSION["url"]["prevPage"] = $page;
    }

    public function getPrevPage() {
        return $this->previousPage;
    }
}

?>

