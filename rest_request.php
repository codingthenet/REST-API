<?php

/*
 * Stores information on and executes REST requests.
 * See example requests in request.php.
 */
class RestRequest {
  private $_curlHandle;
  private $_method;
  private $_responseBody;
  private $_responseInfo;
  
  /*
   * Sets the initial parameters of the cURL request
   */
  public function __construct() {
    $this->_curlHandle = curl_init();
    curl_setopt($this->_curlHandle, CURLOPT_TIMEOUT, 10);
    curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->_curlHandle, CURLOPT_HTTPHEADER, array('Accept: application/json'));
  }
  
  /*
   * Sets the important parameters for the cURL request.
   * Parameters can be extended by adding to the array.
   */
  public function make($_method, $url, $path, $body = array()) {
    $body = json_encode($body, JSON_FORCE_OBJECT);
    
    $urlParams = array(
      'path' => $path,
    );
    
    // Build the query URL
    $url = $url . '?'. http_build_query($urlParams, '' , '&');
    
    switch ($_method) {
      case 'POST':
        // Build the post query from associative array
        $postBody = http_build_query(array('data' => $body), '', '&');
        // Set the number of params to be posted
        curl_setopt($this->_curlHandle, CURLOPT_POST, 1);
        // Set the post body
        curl_setopt($this->_curlHandle, CURLOPT_POSTFIELDS, $postBody);
        break;
      case 'PUT':
        $requestLength = strlen($body);
        
        $fileHandle = fopen('php://temp', 'rw');
        fwrite($fileHandle, $body);
        rewind($fileHandle);
        
        curl_setopt($this->_curlHandle, CURLOPT_INFILE, $fileHandle);
        curl_setopt($this->_curlHandle, CURLOPT_INFILESIZE, $requestLength);
        curl_setopt($this->_curlHandle, CURLOPT_PUT, TRUE);
        break;
      case 'DELETE':
        // Set the delete HTTP request _method
        curl_setopt($this->_curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
      case 'GET':
        // Included for completeness - otherwise an exception will be thrown
        break;
      default:
        $msg = $_method . ' is not a valid request _method.'
              .' The type should be one of: GET, POST, PUT, DELETE';
        throw new Exception($msg);
        exit();
    }
    
    curl_setopt($this->_curlHandle, CURLOPT_URL, $url);
    
    return $this->_execute();
  }
  
  private function _execute() {
    $this->_responseBody = curl_exec($this->_curlHandle);
    $this->_responseInfo  = curl_getinfo($this->_curlHandle);
    $this->close();
  }
  
  public function responseBody() {
    return $this->_responseBody;
  }
  
  public function responseInfo() {
    return $this->_responseInfo;
  }
  
  public function __toString() {
    if ($this->_responseBody) {
      return $this->_responseBody;
    }
    else {
      return 'This request has not yet been executed';
    }
  }
  
  public function close() {
    if (gettype($this->_curlHandle) === 'resource') {
      curl_close($this->_curlHandle);
    }
  }
  
  /*
   * Explicitly close the connection on destruct,
   * in case the request hasn't been executed.
   */
  public function __destruct() {
    $this->close();
  }
}

?>