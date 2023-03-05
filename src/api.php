<?php
  include_once __DIR__ . "/header.php";

  header('Content-type: application/json');

  $_escape = fn($str) => preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", (string) $str);

  $marker_id = 0;
  $places = mysql_query("SELECT * FROM places WHERE approved='1' ORDER BY title");
  $places_total = mysql_num_rows($places);
  
  echo '{ "type": "FeatureCollection", "features": [';
  
  while($place = mysql_fetch_assoc($places)) {
    $newplace = [];
    $newplace["type"] = "Feature";
    $newplace["properties"] = ["title" => $_escape( $place[\TITLE] ), "description" => $_escape( $place[\DESCRIPTION] ), "uri" => $_escape( $place[\URI] ), "address" => $_escape( $place[\ADDRESS] ), "type" => $_escape( $place[\TYPE] )];
    $newplace["geometry"] = ["type" => "Point", "coordinates" => [$place[\LNG] * 1.0, $place[\LAT] * 1.0]];

    if( $marker_id > 0 ){
      echo ',';
    }
    echo json_encode( $newplace );
    
    $marker_id++;
  }
  
  echo '] }';
  
?>