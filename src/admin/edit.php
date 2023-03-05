<?php
include __DIR__ . "/header.php";


if (isset($_GET['place_id'])) {
    $place_id = htmlspecialchars((string) $_GET['place_id']);
} elseif (isset($_POST['place_id'])) {
    $place_id = htmlspecialchars((string) $_POST['place_id']);
} else {
  exit; 
}


// get place info
$place_query = mysqli_query(sprintf("SELECT * FROM places WHERE id='%s' LIMIT 1", $place_id));
if(mysqli_num_rows($place_query) != 1) { exit; }

$place = mysqli_fetch_assoc($place_query);


// do place edit if requested
if($task == "doedit") {
  $title = str_replace( "'", "\\'", str_replace( "\\", "\\\\", (string) $_POST['title'] ) );
  $type = $_POST['type'];
  $address = str_replace( "'", "\\'", str_replace( "\\", "\\\\", (string) $_POST['address'] ) );
  $uri = $_POST['uri'];
  $description = str_replace( "'", "\\'", str_replace( "\\", "\\\\", (string) $_POST['description'] ) );
  $owner_name = str_replace( "'", "\\'", str_replace( "\\", "\\\\", (string) $_POST['owner_name'] ) );
  $owner_email = $_POST['owner_email'];
  $lat = (float) $_POST['lat'];
  $lng = (float) $_POST['lng'];
  
  mysqli_query(sprintf("UPDATE places SET title='%s', type='%s', address='%s', uri='%s', lat='%s', lng='%s', description='%s', owner_name='%s', owner_email='%s' WHERE id='%s' LIMIT 1", $title, $type, $address, $uri, $lat, $lng, $description, $owner_name, $owner_email, $place_id)) || die(mysql_error());
  
  // geocode
  //$hide_geocode_output = true;
  //include "../geocode.php";
  
  header(sprintf('Location: index.php?view=%s&search=%s&p=%s', $view, $search, $p));
  exit;
}

?>



<? echo $admin_head; ?>

<form id="admin" class="form-horizontal" action="edit.php" method="post">
  <h1>
    Edit Place
  </h1>
  <fieldset>
    <div class="control-group">
      <label class="control-label" for="">Title</label>
      <div class="controls">
        <input type="text" class="input input-xlarge" name="title" value="<?=$place[\TITLE]?>" id="">
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Type</label>
      <div class="controls">
        <select class="input input-xlarge" name="type">
          <option<? if($place[type] == "startup") {?> selected="selected"<? } ?>>startup</option>
          <option<? if($place[type] == "accelerator") {?> selected="selected"<? } ?>>accelerator</option>
          <option<? if($place[type] == "incubator") {?> selected="selected"<? } ?>>incubator</option>
          <option<? if($place[type] == "coworking") {?> selected="selected"<? } ?>>coworking</option>
          <option<? if($place[type] == "investor") {?> selected="selected"<? } ?>>investor</option>
          <option<? if($place[type] == "service") {?> selected="selected"<? } ?>>service</option>
          <option<? if($place[type] == "hackerspace") {?> selected="selected"<? } ?>>hackerspace</option>
        </select>
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Address</label>
      <div class="controls">
        <input type="text" class="input input-xlarge" name="address" value="<?=$place[\ADDRESS]?>" id="">
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">URL</label>
      <div class="controls">
        <input type="text" class="input input-xlarge" name="uri" value="<?=$place[\URI]?>" id="">
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Description</label>
      <div class="controls">
        <textarea class="input input-xlarge" name="description"><?=$place[\DESCRIPTION]?></textarea>
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Submitter Name</label>
      <div class="controls">
        <input type="text" class="input input-xlarge" name="owner_name" value="<?=$place[\OWNER_NAME]?>" id="">
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Submitter Email</label>
      <div class="controls">
        <input type="text" class="input input-xlarge" name="owner_email" value="<?=$place[\OWNER_EMAIL]?>" id="">
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="">Location</label>
      <div class="controls">
        <input type="hidden" name="lat" id="mylat" value="<?=$place[\LAT]?>"/>
        <input type="hidden" name="lng" id="mylng" value="<?=$place[\LNG]?>"/>
        <div id="map" style="width:80%;height:300px;">
        </div>
        <script type="text/javascript">
          var map = new google.maps.Map( document.getElementById('map'), {
            zoom: 17,
            center: new google.maps.LatLng( <?=$place[\LAT]?>, <?=$place[\LNG]?> ),
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: false,
            mapTypeControl: false
          });
          var marker = new google.maps.Marker({
            position: new google.maps.LatLng( <?=$place[\LAT]?>, <?=$place[\LNG]?> ),
            map: map,
            draggable: true
          });
          google.maps.event.addListener(marker, 'dragend', function(e){
            document.getElementById('mylat').value = e.latLng.lat().toFixed(6);
            document.getElementById('mylng').value = e.latLng.lng().toFixed(6);
          });
        </script>
      </div>
    </div>    
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <input type="hidden" name="task" value="doedit" />
      <input type="hidden" name="place_id" value="<?=$place[\ID]?>" />
      <input type="hidden" name="view" value="<?=$view?>" />
      <input type="hidden" name="search" value="<?=$search?>" />
      <input type="hidden" name="p" value="<?=$p?>" />
      <a href="index.php" class="btn" style="float: right;">Cancel</a>
    </div>
  </fieldset>
</form>



<? echo $admin_foot; ?>
