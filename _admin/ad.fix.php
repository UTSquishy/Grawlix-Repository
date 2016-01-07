<?php

/*****
 * ! Setup
 */

require_once('panl.init.php');

$message = new GrlxAlert;

$view = new GrlxView;
$list = new GrlxList;
$list->draggable(false);

$view-> yah = 10;

// Default value
$page_title = 'Ad table fix';

$db->rawQuery ("ALTER TABLE grlx_ad_reference ADD title VARCHAR(32)", NULL, NULL);



/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
$output .= $content_output;

print($output);

?>

<?php
$output = $view->close_view();
print($output);
