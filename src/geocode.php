<?php
include_once __DIR__ . "/header.php";

// Run this script after new markers have been added to the DB.
// It will look for any markers that are missing latlong values
// and automatically geocode them.

// google maps vars
define("MAPS_HOST", "maps.googleapis.com");

// geocode all markers
geocode("places");
geocode("events");




// geocode function
function geocode($table) {
  global $hide_geocode_output;

  // get places that don't have latlong values
  ($result = mysql_query(sprintf('SELECT * FROM %s WHERE lat=0 OR lng=0', $table))) || die(mysql_error());

  // geocode and save them back to the db
  $delay = 0;
  $base_url = "http://" . MAPS_HOST . "/maps/api/geocode/xml";

  // Iterate through the rows, geocoding each address
  while ($row = @mysqli_fetch_assoc($result)) {
    $geocode_pending = true;

    while ($geocode_pending) {
      $address = $row["address"];
      $id = $row["id"];
      $request_url = $base_url . "?address=" . urlencode((string) $address) . "&sensor=false";
      ($xml = simplexml_load_file($request_url)) || die("url not loading");
      
      $status = $xml->status;
      if ($status == "OK") {
          // Successful geocode
          $geocode_pending = false;
          $coordinates = $xml->result->geometry->location;
          // Format: Longitude, Latitude, Altitude
          $lat = $coordinates->lat;
          $lng = $coordinates->lng;
          $query = sprintf(sprintf('UPDATE %s ', $table) .
                " SET lat = '%s', lng = '%s' " .
                " WHERE id = '%s' LIMIT 1;",
                mysql_real_escape_string($lat),
                mysql_real_escape_string($lng),
                mysql_real_escape_string($id));
          $update_result = mysql_query($query);
          if (!$update_result) {
            die("Invalid query: " . mysql_error());
          }
      } elseif (strcmp($status, "620") == 0) {
          // sent geocodes too fast
          $delay += 100000;
      } else {
        // failure to geocode
        $geocode_pending = false;
        //echo "Address " . $address . " failed to geocoded. ";
        //echo "Received status " . $status . " \n";
      }
      
      usleep($delay);
    }
  }

  // finish
  if(@$hide_geocode_output != true) {
    echo mysqli_num_rows($result).sprintf(' %s geocoded<br />', $table);
  }

}

?>
