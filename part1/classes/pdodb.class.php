<?php
/**
* PDO (PHP Data Objects) Database Connection
* Create a singleton SQLite database connection
* @author Nathan Edmond
*/
class PDOdb {
 private static $dbConnection = null;

 /**
 * Make this private to prevent normal class instantiation 
 * @access private
 */
 private function __construct() {
 }

 /**
 * Return DB connection or create initial connection
 * @return object (PDO)
 * @access public
 */
 public static function getConnection($dbname) {
   if ( !self::$dbConnection ) {
     try {
       self::$dbConnection = new PDO("sqlite:".$dbname);
       self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     }
     catch( PDOException $e ) {
       error_log($e->getMessage());
     }
   }
   return self::$dbConnection;
 }
}
?>