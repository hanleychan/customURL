<?php
define("BASE_URL", "/url/");
define("ROOT_PATH", $_SERVER["DOCUMENT_ROOT"] . "/url/");

require_once(ROOT_PATH . "includes/db_config.php");
require_once(ROOT_PATH . "includes/database.php");
require_once(ROOT_PATH . "includes/databaseObject.php");
require_once(ROOT_PATH . "includes/website.php");
require_once(ROOT_PATH . "includes/pagination.php");
require_once(ROOT_PATH . "includes/admin.php");

?>
