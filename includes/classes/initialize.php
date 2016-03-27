<?php
define("BASE_URL", "/url/");
define("ROOT_PATH", $_SERVER["DOCUMENT_ROOT"] . "/url/");

date_default_timezone_set('America/Vancouver');

require_once(ROOT_PATH . "includes/classes/db_config.php");
require_once(ROOT_PATH . "includes/classes/database.php");
require_once(ROOT_PATH . "includes/classes/databaseObject.php");
require_once(ROOT_PATH . "includes/classes/website.php");
require_once(ROOT_PATH . "includes/classes/pagination.php");
require_once(ROOT_PATH . "includes/classes/admin.php");
require_once(ROOT_PATH . "includes/classes/session.php");

?>
