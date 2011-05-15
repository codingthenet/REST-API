<?php

require_once('rest_request.php');

$requestBody = array(
  'title' => 'AARDVARK ASSASSIN',
  'description' => 'A film about a lovable Aardvark and his ants',
  'release_year' => '2005',
  'language_id' => '1',
  'rental_duration' => '7',
  'rental_rate' => '7.99',
  'length' => '120',
  'replacement_cost' => '10.99',
  'rating' => 'R',
  'special_features' => 'Trailers',
);

$request = new RestRequest();

try {
  //$request->make('PUT', 'http://localhost/rest/rest_service.php', 'film/76', $requestBody);
  //$request->make('GET', 'http://localhost/rest/rest_service.php', 'film');
  //$request->make('GET', 'http://localhost/rest/rest_service.php', 'film');
  $request->make('DELETE', 'http://localhost/rest/rest_service.php', 'film/25');
  //$request->make('POST', 'http://localhost/rest/rest_service.php', 'film', $requestBody);
}
catch (Exception $e) {
  echo 'Error: ' . $e->getMessage();
  exit;
}

?>

<pre>
<b>--responseBody</b><br/>
<?php print_r(json_decode($request->responseBody())); ?>

<b>--responseInfo</b><br/>
<?php print_r($request->ResponseInfo()); ?>
</pre>