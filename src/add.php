<?php
include_once __DIR__ . "/header.php";

// This is used to submit new markers for review.
// Markers won't appear on the map until they are approved.

$owner_name = mysql_real_escape_string(parseInput($_POST['owner_name']));
$owner_email = mysql_real_escape_string(parseInput($_POST['owner_email']));
$title = mysql_real_escape_string(parseInput($_POST['title']));
$type = mysql_real_escape_string(parseInput($_POST['type']));
$address = mysql_real_escape_string(parseInput($_POST['address']));
$uri = mysql_real_escape_string(parseInput($_POST['uri']));
$description = mysql_real_escape_string(parseInput($_POST['description']));

// validate fields
if (empty($title) || empty($type) || empty($address) || empty($uri) || empty($description) || empty($owner_name) || empty($owner_email)) {
    echo "All fields are required - please try again.";
    exit;
} elseif ($sg_enabled) {
    // if startup genome mode enabled, post new data to API
    try {
      @$r = $http->doPost("/organization", $_POST);
      $response = json_decode((string) $r, 1, 512, JSON_THROW_ON_ERROR);
      if ($response['response'] == 'success') {
        include_once(__DIR__ . "/startupgenome_get.php");
        echo "success"; 
        exit;
      }
    } catch (Exception $e) {
      echo "<pre>";
      print_r($e);
    }
    // normal mode enabled, save new data to local db
} else {

  // insert into db, wait for approval
  ($insert = mysql_query("INSERT INTO places (approved, title, type, address, uri, description, owner_name, owner_email) VALUES (null, '$title', '$type', '$address', '$uri', '$description', '$owner_name', '$owner_email')")) || die(mysql_error());

  // geocode new submission
  $hide_geocode_output = true;
  include __DIR__ . "/geocode.php";
  
  echo "success";
  exit;

}


?>
