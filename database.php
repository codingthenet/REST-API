<?php

/*
 * An uninstantiable (but extensible) database class.
 * Interacts with the database using PDO.
 */
abstract class DB {
  private static $_dbHost = "localhost";
  private static $_dbUser = "root";
  private static $_dbPass = "";
  private static $_dbName = "sakila";
  private static $_connection;
  
  /*
   * Connects to the database, or reports an error
   */
  private static function _connect() {
    if (!isset(self::$_connection)) {
      try {
        // No clean way to check for timeout of the connection with PDO
        // - can only check timeout of queries. Reports errors with a longer
        // cURL timeout, but would be nice to set a time limit on this
        self::$_connection = new PDO('mysql:host='.self::$_dbHost.';dbname='.self::$_dbName, self::$_dbUser, self::$_dbPass);
      }
      catch (PDOException $e) {
        return 'Connection Error: ' . $e->getMessage();
        exit;
      }
    }
  }
  
  /*
   * Simple wrapper for queries.
   * Needs to return more info about operation (for example, affected rows).
   */
  public static function query($sql, $params = array()) {
    self::_connect();
    
    // Query the database
    $query = self::$_connection->prepare($sql);
    $query->execute($params);
    
    // Fetch an associative array of the results
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    $rowCount = $query->rowCount();
    
    // If there's no result but there are affected rows,
    // there has been an INSERT, UPDATE or DELETE
    if (empty($result) && $rowCount) {
      // Trim the operation from the SQL query
      $op = substr($sql, 0, 6);
      // Return a message like 'Successful DELETE'
      $result[$op] = "Successful $op";
    }
    // If the result is empty and no rows were affected, the operation failed
    else if (empty($result) && !$rowCount) {
      $result['FAILURE'] = 'Your operation had no effect';
    }
    
    $result['ROWCOUNT'] = $rowCount;
    return $result;
  }
  
  /*
   * Closes the connection to the database
   */
  public static function close() {
    self::$_connection = NULL;
  }
}

?>