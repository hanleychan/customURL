<?php
class Website extends DatabaseObject {
    public $id;
    public $url;
    public $shortname;
    public $hits;
    public $added;
    const MAX_SHORTNAME_LENGTH = 50;
    const UNALLOWED_NAMES = ["all", "admin", "addURL", "logout"];
    protected static $table_name = "websites";
    protected static $db_fields = array('id', 'url', 'shortname', 'hits', 'added');
    
    /**
     * Adds http:// to front of url string if it is missing a protocol
     */
    public static function addScheme($url, $scheme = 'http://') {
        return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
    }
    
    /**
     * Returns whether a URL is valid
     */
    public static function isValidURL($url) {
        if(filter_var(self::addScheme($url), FILTER_VALIDATE_URL)) {
            return true;
        }
        else {
            return false;
        }
    }
  
    /**
     * Returns whether a shortened URL name is valid
     */  
    public static function isValidName($name) {
        // name only contain letters, numbers, underscores, dashes and be less than MAX_SHORTNAME_LENGTH characters
        if(preg_match("/^[a-zA-Z0-9_-]+$/", $name) && !in_array($name, self::UNALLOWED_NAMES) && strlen($name) <= self::MAX_SHORTNAME_LENGTH) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Returns a website object from a specified shortened url name
     */
    public static function getWebsiteByName($db, $name) {
        $sql = "SELECT * FROM " . self::$table_name . " WHERE shortname = ? LIMIT 1";
        $paramArray = array($name);
        $result = self::findBySql($db, $sql, $paramArray);
        
        if($result) {
            return $result[0];
        }
        else {
            return false;
        }        
    }
    
    /**
     * Returns an array of the latest website objects added
     */
    public static function getLatest($db, $number=10) {
        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY added DESC LIMIT " . (int)$number;
        $result = self::findBySqL($db, $sql);
        
        if($result) {
            return $result;
        }    
        else {
            return false;
        }
    }
    
    /**
     * Returns an array of the top website objects
     */
    public static function getTopHits($db, $number=10) {
        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY hits DESC, added DESC LIMIT " . (int)$number;
        $result = self::findBySql($db, $sql);
        
        if($result) {
            return $result;
        }
        else {
            return false;
        }
    }
  
    /**
     * Returns an array of website objects filtered by a search value
     */  
    public static function getByFilter($db, $limit=10, $offset=0, $search="", $sort="added", $sortOrder="desc") {
        $safeSort = self::getSortColumnValue($sort);
        $safeSortOrder = self::getSortOrderValue($sortOrder);

        $sql = "SELECT * FROM " . self::$table_name . " WHERE url LIKE ? OR shortname LIKE ? ORDER BY {$safeSort} {$safeSortOrder} LIMIT {$offset}, {$limit}";
        $paramArray = array("%$search%", "%$search%");
        
        $result = self::findBySql($db, $sql, $paramArray);
        
        if($result) {
            return $result;
        }
        else {
            return false;
        }
    }

    public static function getNumEntriesFromFilter($db, $search="", $sort="added", $sortOrder="desc") {
        $safeSort = self::getSortColumnValue($sort);
        $safeSortOrder = self::getSortOrderValue($sortOrder);

        $sql = "SELECT COUNT(*) FROM " . self::$table_name . " WHERE url LIKE ? OR shortname LIKE ? ORDER BY {$safeSort} {$safeSortOrder} LIMIT 1";
        $paramArray = array("%$search%", "%$search%");

        try {
            $result = $db->prepare($sql); 
            $result->execute($paramArray);
        }
        catch(Exception $e) {
            die($e->getMessage());
        }

        if($result) {
            return (int)$result->fetch(PDO::FETCH_NUM)[0];
        }
        else {
            return 0;
        }
    }
    
    /**
     * Returns all websites sorted by a specified column 
     */
     public static function getAllSorted($db, $limit=10, $offset, $sort="added", $sortOrder="DESC") {
        $safeSort = self::getSortColumnValue($sort);
        $safeSortOrder = self::getSortOrderValue($sortOrder);

        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY {$safeSort} {$safeSortOrder} LIMIT {$offset}, {$limit}";
        
        $result = self::findBySql($db, $sql);
        
        if($result) {
            return $result;
        }
        else {
            return false;
        }
     }

    /**
     * Validates and returns the column value to sort by
     */
    private static function getSortColumnValue($sort) {
        switch($sort) {
            case "hits":
                return "hits";
                break;
            case "fullURL":
                return "url";
                break;
            case "customURL";
                return "shortname";
                break;
            case "added":
            default:
                return "added";
                break;
        }
    }

    /**
     * Validates and returns the the order to sort by
     */
    private static function getSortOrderValue($sortOrder) {
        if($sortOrder === "desc" || $sortOrder === "asc") {
            return strtoupper($sortOrder);
        }
        else {
            return "DESC";
        }
    }
}

?>

