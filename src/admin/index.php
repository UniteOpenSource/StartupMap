<?php
$page = "index";
include __DIR__ . "/header.php";


// hide marker on map
if($task == "hide") {
  $place_id = htmlspecialchars((string) $_GET['place_id']);
  mysqli_query(sprintf("UPDATE places SET approved=0 WHERE id='%s'", $place_id)) || die(mysql_error());
  header(sprintf('Location: index.php?view=%s&search=%s&p=%s', $view, $search, $p));
  exit;
}

// show marker on map
if($task == "approve") {
  $place_id = htmlspecialchars((string) $_GET['place_id']);
  mysqli_query(sprintf("UPDATE places SET approved=1 WHERE id='%s'", $place_id)) || die(mysql_error());
  header(sprintf('Location: index.php?view=%s&search=%s&p=%s', $view, $search, $p));
  exit;
}

// completely delete marker from map
if($task == "delete") {
  $place_id = htmlspecialchars((string) $_GET['place_id']);
  mysqli_query(sprintf("DELETE FROM places WHERE id='%s'", $place_id)) || die(mysql_error());
  header(sprintf('Location: index.php?view=%s&search=%s&p=%s', $view, $search, $p));
  exit;
}

// paginate
$items_per_page = 15;
$page_start = ($p-1) * $items_per_page;
$page_end = $page_start + $items_per_page;

// get results
if ($view == "approved") {
    $places = mysqli_query(sprintf("SELECT * FROM places WHERE approved='1' ORDER BY title LIMIT %s, %d", $page_start, $items_per_page));
    $total = $total_approved;
} elseif ($view == "rejected") {
    $places = mysqli_query(sprintf("SELECT * FROM places WHERE approved='0' ORDER BY title LIMIT %s, %d", $page_start, $items_per_page));
    $total = $total_rejected;
} elseif ($view == "pending") {
    $places = mysqli_query(sprintf('SELECT * FROM places WHERE approved IS null ORDER BY id DESC LIMIT %s, %d', $page_start, $items_per_page));
    $total = $total_pending;
} elseif ($view == "") {
    $places = mysqli_query(sprintf('SELECT * FROM places ORDER BY title LIMIT %s, %d', $page_start, $items_per_page));
    $total = $total_all;
}

if($search != "") {
  $places = mysqli_query(sprintf("SELECT * FROM places WHERE title LIKE '%%%s%%' ORDER BY title LIMIT %s, %d", $search, $page_start, $items_per_page));
  $total = mysqli_num_rows(mysqli_query(sprintf("SELECT id FROM places WHERE title LIKE '%%%s%%'", $search))); 
}

echo $admin_head;
?>


<div id="admin">
  <h3>
    <? if($total > $items_per_page) { ?>
      <?=$page_start+1?>-<? if($page_end > $total) { echo $total; } else { echo $page_end; } ?>
      of <?=$total?> markers
    <? } else { ?>
      <?=$total?> markers
    <? } ?>
  </h3>
  <ul>
    <?
      while($place = mysql_fetch_assoc($places)) {
        $place[uri] = str_replace("http://", "", $place[uri]);
        $place[uri] = str_replace("https://", "", $place[uri]);
        $place[uri] = str_replace("www.", "", $place[uri]);
        echo "
          <li>
            <div class='options'>
              <a class='btn btn-small' href='edit.php?place_id=$place[id]&view=$view&search=$search&p=$p'>Edit</a>
              ";
              if($place[approved] == 1) {
                echo "
                  <a class='btn btn-small btn-success disabled'>Approve</a>
                  <a class='btn btn-small btn-inverse' href='index.php?task=hide&place_id=$place[id]&view=$view&search=$search&p=$p'>Reject</a>
                ";
              } else if(is_null($place[approved])) {
                echo "
                  <a class='btn btn-small btn-success' href='index.php?task=approve&place_id=$place[id]&view=$view&search=$search&p=$p'>Approve</a>
                  <a class='btn btn-small btn-inverse' href='index.php?task=hide&place_id=$place[id]&view=$view&search=$search&p=$p'>Reject</a>
                ";
              } else if($place[approved] == 0) {
                echo "
                  <a class='btn btn-small btn-success' href='index.php?task=approve&place_id=$place[id]&view=$view&search=$search&p=$p'>Approve</a>
                  <a class='btn btn-small btn-inverse disabled'>Reject</a>
                ";
              }
              echo "
              <a class='btn btn-small btn-danger' href='index.php?task=delete&place_id=$place[id]&view=$view&search=$search&p=$p'>Delete</a>
            </div>
            <div class='place_info'>
              <a href='http://$place[uri]' target='_blank'>
                $place[title]
                <span class='url'>
                  $place[uri]
                </span>
              </a>
            </div>
          </li>
        ";
      }
    ?>
  </ul>
  
  <? if($p > 1 || $total >= $items_per_page) { ?>
    <ul class="pager">
      <? if($p > 1) { ?>
        <li class="previous">
          <a href="index.php?view=<?=$view?>&search=<?=$search?>&p=<? echo $p-1; ?>">&larr; Previous</a>
        </li>
      <? } ?>
      <? if($total >= $items_per_page * $p) { ?>
        <li class="next">
          <a href="index.php?view=<?=$view?>&search=<?=$search?>&p=<? echo $p+1; ?>">Next &rarr;</a>
        </li>
      <? } ?>
    </ul>
  <? } ?>

</div>


<? echo $admin_foot ?>
