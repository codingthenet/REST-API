<?php

require_once('database.php');

/*
 * The parent class that serves our REST API
 */
abstract class RestServe {
  /*
   * The core response method - detects the type of request,
   * and takes the appropriate action(s)
   */
  public static function response() {
    // Get the request method - must be one of GET, POST, PUT, DELETE
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Separate the path elements
    self::_checkPath($_GET['path'], $method);
    $path = explode('/', $_GET['path']);
   
    $checkData = TRUE;
    
    // Take action depending on the method. POST needs
    // the data from the transaction, PUT needs the PHP
    // data stream, GET and DELETE don't need anything 
    // since they get all of their data from the path.
    switch ($method) {
      case 'POST':
        $data = $_POST['data'];
        break;
      case 'PUT':
        // Reads the JSON encoded body from the PHP I/O stream
        $data = file_get_contents('php://input');
        break;
      case 'GET':
      case 'DELETE':
        $data = NULL;
        $checkData = FALSE;
        break;
      default:
        self::_error('Invalid Method');
    }
    
    if ($checkData && empty($data)) {
      self::_error('No Data');
    }
    
    $request = new RestCall($method, $data, $path);
    $response = $request->execute();
    
    // Respond based on the action
    if (isset($response['UPDATE']) || isset($response['DELETE'])) {
      self::_setHeader(200, 'json');
      echo json_encode($method . ' successful');
      return;
    }
    else if (empty($response) || $response === FALSE) {
      return self::_error('No Response');
    } else {
      self::_setHeader(200, 'json');
      echo json_encode($response, JSON_FORCE_OBJECT);
      return;
    }
  }
  
  /*
   * Checks that the path is in the correct format;
   *   For GET requests we can have film or film/[film_id]
   *   For all other requests we need film/[film_id]
   */
  private static function _checkPath($path, $method) {
    if ($method === 'GET') {
      // Matches film or film/[int with 1 or more digits]
      $valid = preg_match('/^film(\/[0-9]+?)?$/i', $path);
    }
    else {
      // Matches film/[int with 1 or more digits]
      $valid = preg_match('/^film\/[0-9]+?$/i', $path);
    }
    
    if (!$valid) {
      self::_error('Invalid Path');
    }
  }
  
  /*
   * Sets the response type and format header,
   * with an optional message
   */
  private static function _setHeader($response = 200, $content = 'html', $msg = '') {
    // First set the response header
    switch($response) {
      case (200):
        $r = '200 OK';
        break;
      case (201):
        $r = '201 Created';
        break;
      case (500):
        $r = '500 Internal Server Error';
        break;
    }
    
    // Then set the content-type header
    switch ($content) {
      case ('html'):
        $c = 'text/html';
        break;
      case ('json'):
        $c = 'application/json';
        break;
      case ('plain'):
        $c = 'text/plain';
        break;
    }
    
    header("HTTP/1.1 $r");
    header("Content-type: $c");
  }
  
  /*
   * Sets an error header and message
   */
  private static function _error($message = 'Undefined') {
    self::_setHeader(500, html);
    echo json_encode(array('ERROR' => $message));
    exit;
  }
}

/*
 * The class that processes the request,
 * and stores the main parameters.
 */
class RestCall {
  private $_method;
  private $_data;
  private $_path;
  
  /*
   * Safe columns to query in film table
   */
  private $_safeColumns = array(
    'title',
    'description',
    'release_year',
    'language_id',
    'original_language_id',
    'rental_duration',
    'rental_rate',
    'length',
    'replacement_cost',
    'rating',
    'special_features',
  );
  
  /*
   * Checks that an array of columns are safe to use
   */
  private function _checkColumns($array) {
    $safe = (array_diff($array, $this->_safeColumns)) ? FALSE : TRUE;
    return $safe;
  }
  
  /*
   * Constructor - saves the method, data, and request path
   */
  public function __construct($method, $data, $path) {
    $this->_method = $method;
    $this->_path = $path;
    
    if ($data !== NULL) {
      $this->_data = json_decode($data);
    }
  }
  
  /*
   * Execution method - prepares data for a database call
   */
  public function execute() {
    // If the method is POST or PUT, prepare the data
    // and check that all columns are valid
    if ($this->_method === 'POST' || $this->_method === 'PUT') {
      $data = (array) $this->_data;
      if (empty($data) || !$this->_checkColumns(array_keys($data))) {
        return FALSE;
      }
    }
    
    switch ($this->_method) {
      case 'GET':
        $sql = "SELECT * FROM film";
        
        if (isset($this->_path[1])) {
          $sql .=  " WHERE film_id = ?";
          $params = array($this->_path[1]);
        }
        break;
      case 'DELETE':
        $sql = "DELETE FROM film WHERE film_id = ?";
        $params = array($this->_path[1]);
        break;
      case 'POST':
        $placeholders = array();
        
        foreach ($data as $param) {
          $placeholders[] = '?';
        }
        
        $columnString = implode(', ', array_keys($data));
        $placeholderString = implode(', ', $placeholders);
        
        // Params cannot be associative array
        $params = array_values($data);
        $sql = "INSERT INTO film ($columnString) VALUES ($placeholderString)";
        break;
      case ('PUT'):
        $columnString = implode(' = ?, ', array_keys($data));
        $sql = "UPDATE film SET $columnString  = ? WHERE film_id = ?";
        
        // Params cannot be associative array
        $params = array_merge(array_values($data), array($this->_path[1]));
        break;
    }
    
    $result = db::query($sql, $params);
    
    if (empty($result) || isset($result['failure'])) {
      return FALSE;
    }
    else {
      return $result;
    }
  }
  
  /*
   * Returns request method
   */
  public function getMethod() {
    return $this->_method;
  }
  
  /*
   * Returns data sent for request
   */
  public function getData() {
    return $this->_data;
  }
}

/*
 * Catches and responds to incoming requests
 */
RestServe::response();

?>