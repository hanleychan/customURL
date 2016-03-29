<?php
define("BASE_URL", "/url/src/public/");
define("ROOT_PATH", $_SERVER["DOCUMENT_ROOT"] . "/url/src/");

date_default_timezone_set('America/Vancouver');

require_once(ROOT_PATH . "classes/db_config.php");
require_once(ROOT_PATH . "classes/database.php");
require_once(ROOT_PATH . "classes/databaseObject.php");
require_once(ROOT_PATH . "classes/website.php");
require_once(ROOT_PATH . "classes/pagination.php");
require_once(ROOT_PATH . "classes/admin.php");
require_once(ROOT_PATH . "classes/session.php");

?>
