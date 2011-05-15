A REST API that operates on the film table of the sakila demo database from 
MySQL (available at http://dev.mysql.com/doc/sakila/en/sakila/html).

Uses the four verbs, PUT, POST, GET, and DELETE, along with a simple path 
system.

 - GET can select an individual film by id (where path is film/[film_id]), or 
   all films (where path is film)

 - DELETE can delete any film by id via path film/[film_id]

 - POST can insert a film via path film, using an associative array of column
   values (which is json-encoded by the class)
 
 - PUT can update any film using the same associative array via film/[film_id]
