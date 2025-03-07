<?php
include_once __DIR__ . "/header.php";

// This script syncs your database with Startup Genome.
// It checks to see if your local database is missing any
// organizations that have been added to your Startup Genome map.
// If it finds any, it will add them to your local database.

// This script will only run if we haven't checked for new only
// if the frequency interval specified in db.php has already passed.

$interval_query = mysqli_query("SELECT sg_lastupdate FROM settings LIMIT 1");
if(mysqli_num_rows($interval_query) == 1) {
  $interval_info = mysqli_fetch_assoc($interval_query);
  if((time()-$interval_info[\SG_LASTUPDATE]) > $sg_frequency || $_GET['override'] == "true") {

    // connect to startup genome API
    if(str_contains((string) $_SERVER['SERVER_NAME'],'.local')) {
      $config = ['api_url' => 'startupgenome.com.local/api/'];
    } else {
      $config = ['api_url' => 'startupgenome.co/api'];
    }
    
    $config['search_location'] = $sg_location;
    $http = Http::connect($config['api_url'],false,'http');

    try {
      $r = $http->doGet(sprintf('login/%s', $sg_auth_code));
      $j = json_decode($r,1, 512, JSON_THROW_ON_ERROR);
      $http->setHeaders([sprintf('AUTH-CODE: %s', $sg_auth_code)]);
      $user = $j['response'];

    } catch(Exception $exception) {
      $error = "<div class='error'>".print_r($exception)."</div>";
      exit();
    }

    // get organizations
    try {
      $r = $http->doGet(sprintf('/organizations%s', $config['search_location']));
      $places_arr = json_decode($r, 1, 512, JSON_THROW_ON_ERROR);

      // update organizations in local db
      $org_array = [];
      foreach ($places_arr['response'] as $place) {
        if (!$place['categories'][0]['parent_category_id'])
          $place['categories'][0]['parent_category_id'] = $place['categories'][0]['category_id'];
        
        switch ($place['categories'][0]['parent_category_id']) {
          default:
          case '2': $place[\TYPE] = 'startup'; break;
          case '3': $place[\TYPE] = 'investor'; break;
          case '4': $place[\TYPE] = 'accelerator'; break;
          case '5': $place[\TYPE] = 'incubator'; break;
          case '6': $place[\TYPE] = 'coworking'; break;
        }

        // format the address for display
        $place[\ADDRESS] = $place['address1'];
        $place[\ADDRESS] .= ($place['address2']?($place[\ADDRESS]?', ':'').$place['address2']:'');
        $place[\ADDRESS] .= ($place['city']?($place[\ADDRESS]?', ':'').$place['city']:'');
        $place[\ADDRESS] .= ($place['state']?($place[\ADDRESS]?', ':'').($states_arr[$place['state']] ?? $place['state']):'');
        $place[\ADDRESS] .= ($place['zip']?($place[\ADDRESS]?', ':'').$place['zip']:'');
        $place[\ADDRESS] .= ($place['country']?($place[\ADDRESS]?', ':'').($countries_arr[$place['country']] ?? $place['country']):'');
        $types_arr[$place[\TYPE]][] = $place;
        $org_array[] = $place['organization_id'];
        ++$count[$place[\TYPE]];
        ++$marker_id;

        ($place_query = mysqli_query("SELECT id FROM places WHERE sg_organization_id='".$place['organization_id']."' LIMIT 1")) || die(mysql_error());

        // organization doesn't exist, add it to the db
        if (mysqli_num_rows($place_query) == 0) {
            mysqli_query("INSERT INTO places (approved,
                                          title,
                                          type,
                                          lat,
                                          lng,
                                          address,
                                          uri,
                                          description,
                                          sg_organization_id
                                          ) VALUES (
                                          '1',
                                          '".parseInput($place['name'])."',
                                          '".parseInput($place['type'])."',
                                          '".parseInput($place['latitude'])."',
                                          '".parseInput($place['longitude'])."',
                                          '".parseInput($place['address'])."',
                                          '".parseInput($place['url'])."',
                                          '".parseInput($place['description'])."',
                                          '".parseInput($place['organization_id'])."'
                                          )") || die(mysql_error());
            // organization already exists, update it with new info if necessary
        } elseif (mysqli_num_rows($place_query) == 1) {
            $place_info = mysqli_fetch_assoc($place_query);
            if($place_info['title'] != $place['name'] || $place_info['type'] != $place['type'] || $place_info['lat'] != $place['latitude'] || $place_info['lng'] != $place['longitude'] || $place_info['address'] != $place['address'] || $place_info['uri'] != $place['url'] || $place_info['description'] != $place['description']) {
              mysqli_query("UPDATE places SET title='".parseInput($place['name'])."',
                                           type='".parseInput($place['type'])."',
                                           lat='".parseInput($place['latitude'])."',
                                           lng='".parseInput($place['longitude'])."',
                                           address='".parseInput($place['address'])."',
                                           uri='".parseInput($place['url'])."',
                                           description='".parseInput($place['description'])."'
                                           WHERE sg_organization_id='".parseInput($place['organization_id'])."' LIMIT 1");
            }
        }
      }

      // delete any old markers that have already been deleted on SG
      $org_array = implode(",", $org_array);
      ($deleted = mysqli_query(sprintf('DELETE FROM places WHERE sg_organization_id NOT IN (%s)', $org_array))) || die(mysql_error());

      // update settings table with the timestamp for this sync
      mysqli_query("UPDATE settings SET sg_lastupdate='".time()."'");

    // show errors if there were any issues
    } catch (Exception $exception) {
      echo "<div class='error'>";
      print_r($exception);
      echo "</div>";
      exit();
    }



  }
}





?>