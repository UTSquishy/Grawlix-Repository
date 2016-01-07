<?php

/* This script is called from the theme manager script.
 */

/*****
 * Setup
 */

require_once('panl.init.php');


/*****
 * Updates
 */

// Prep data
$this_id = $_GET['id'];
$label = $_GET['label'];

if ( $this_id && $label ) {

	// Get info on the selected tone
	$data = array(
		'label' => $label
	);

	$db->where('id',$this_id);
	$id = $db->update('theme_slot', $data);
}
echo '<pre>$_GET|';print_r($_GET);echo '|</pre>';
