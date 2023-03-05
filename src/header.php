<?php
include __DIR__ . "/include/db.php";

// connect to db
mysqli_connect($db_host, $db_user, $db_pass) || die(mysql_error());
mysql_select_db($db_name) || die(mysql_error());

// if map is in Startup Genome mode, check for new data
if($sg_enabled) {
  require_once(__DIR__ . "/include/http.php");
  include_once(__DIR__ . "/startupgenome_get.php");
}

// input parsing
function parseInput($value) {
  $value = htmlspecialchars((string) $value, ENT_QUOTES);
  $value = str_replace("\r", "", $value);
  return str_replace("\n", "", $value);
}



?>