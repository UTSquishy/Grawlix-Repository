<?php

/*****
 * ! Setup
 */

require_once('panl.init.php');

$fileops = new GrlxFileOps;
$message = new GrlxAlert;
$image = new GrlxImage;
$link = new GrlxLinkStyle;
$edit_link = new GrlxLinkStyle;
$delete_link = new GrlxLinkStyle;

$view = new GrlxView;
$list = new GrlxList;
$list->draggable(false);

$view-> yah = 10;

// Default value
$page_title = 'Ad browser';

// URL to PW’s XML feed. ID not included at this point.
$wonderful_file_path = 'http://projectwonderful.com/xmlpublisherdata.php?publisher=';

// Folder in which we store ad pics.
$image_path = $milieu_list['directory']['value'].'/assets/images/ads';

// Get and sanitize every variable sent by _get, _post and _session.
$var_list = array('ad_id','delete_ad_id','current_group','wonderful_id');
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}

// Does the ad image repository exist? If not, try to make it.
$alert_output = $fileops->check_or_make_dir('..'.$image_path);


// List of statuses. Statusae?
/*
$priority_list = array(
	'1' => 'High',
	'0' => 'Normal',
	'-1' => 'Hidden'
);
*/

// Official IAB list, recommended by Brad.
$size_list = array (
	'117,30' => 'Button 117',
	'468,60' => 'Full Banner',
	'728,90' => 'Leaderboard',
//	'336,280' => 'Square 336',
//	'300,250' => 'Square 300',
	'250,250' => 'Square',
	'160,600' => 'Skyscraper',
//	'120,600' => 'Skyscraper 120',
//	'120,240' => 'Small Skyscraper',
//	'240,400' => 'Fat Skyscraper',
	'234,60' => 'Half Banner',
//	'180,150' => 'Rectangle 180',
	'125,125' => 'Square Button',
	'120,90' => 'Button 90',
	'120,60' => 'Button 60',
	'88,31' => 'Button 88&times;31'
);


/*****
 * ! Updates
 */

if ( $_POST && $wonderful_id && $wonderful_id > 0 ) {

	// Enter the ID number.
	$data = array('user_info'=>$wonderful_id);
	$db->where('label','projectwonderful');
	$db->update('third_service',$data);

	// Set PW to active
	$sql = "UPDATE
	grlx_third_match tm,
	grlx_third_service ts
SET
	active = 1
WHERE
	tm.service_id = ts.id
	AND ts.label = ?
";
	$db->rawQuery($sql,array('projectwonderful'));
	$current_group = 'panel-wonderful';
}



if ( $_POST && (!$wonderful_id || $wonderful_id == 0) ) {

	// Zero out the ID.
	$data = array('user_info'=>'');
	$db->where('label','projectwonderful');
	$db->update('third_service',$data);

	// Set PW to inactive
	$sql = "UPDATE
	grlx_third_match tm,
	grlx_third_service ts
SET
	active = 0
WHERE
	tm.service_id = ts.id
	AND ts.label = ?
";
	$db->rawQuery($sql,array('projectwonderful'));
	$current_group = 'panel-wonderful';
}


if ( $delete_ad_id ) {
	$db->where('id', $delete_ad_id);
	if($db->delete('ad_reference')) {
		$alert_output .= $message->success_dialog('Ad deleted. I’m sorry for your loss.');
	}
}

// Let’s upload some artist-submitted graphics, shall we?

$check_these = array(
/*
	'small' => 'small_file',
	'medium' => 'medium_file',
*/
	'large' => 'large_file'
);


if ( $check_these && $_FILES ) {
	foreach ( $check_these as $key => $val ) {

		// Got a new file upload? Then upload it.
		$upload_status[$key] = upload_specific_file($val,$image_path);
		$upload_sizes[$key] = getimagesize('..'.$image_path.'/' . basename($_FILES[$val]['name'][0]));
		$upload_sizes[$key]['url'] = '/assets/images/ads/'. basename($_FILES[$val]['name'][0]);
	}
}

if ( $upload_sizes ) {
	$data = array(); // reset
	foreach ( $upload_sizes as $key => $val ) {
		$data[$key.'_width'] = $val[0];
		$data[$key.'_height'] = $val[1];
		$data[$key.'_image_url'] = $val['url'];
	}
	$data['source_id'] = 1;
	$data['date_created'] = $db-> now();
//echo '<pre>$data|';print_r($data);echo '|</pre>';
	$new_id = $db->insert('ad_reference', $data);
}

if ( $new_id ) {
	header('location:ad.promo-edit.php?ad_id='.$new_id);
	die();
}

if ( $_GET['update_wonderful'] ) {
	$wonderful_third_data = get_third_login('projectwonderful',$db);

	if ( $wonderful_third_data && $wonderful_third_data['active'] == 1 ) {

		@$file_data = $fileops->read_file($wonderful_file_path.$wonderful_third_data['user_info']);
		if ( $file_data ) {
			$file_data = str_replace('pw:', '', $file_data);
			$fileops->set_file('../assets/data/projectwonderful.xml');
			$fileops->set_contents($file_data);
			$fileops->save_file();
		}
	}
}






/*****
 * ! Display logic
 */

// Get all ads.
$ad_list = get_ads(null,$db);

$current_theme = get_site_theme($milieu_list['tone_id']['value'],$db);



///////// Read PW data.

// Get the login ID, if any.
$wonderful_third_data ? $wonderful_third_data : $wonderful_third_data = get_third_login('projectwonderful',$db);


if ( $wonderful_third_data && $wonderful_third_data['active'] == 1 ) {

		// Find the saved XML file.
		$wonderful_xml = file_get_contents('../assets/data/projectwonderful.xml');

		// Got a raw string? Interpret its XML.
		if ( $wonderful_xml ) {
			$wonderful_xml_obj = simplexml_load_string($wonderful_xml);
			$wonderful_ad_list = interpret_wonderful_xml($wonderful_xml_obj,$ad_list,$db);
		}

	// Got wonderful ads? Great! Build a list for the artist.
	if ( $wonderful_ad_list ) {

		$heading_list = array(); // reset
		$heading_list[] = array(
			'value' => 'Ad image',
			'class' => null
		);
		$heading_list[] = array(
			'value' => 'Comic',
			'class' => null
		);
		$heading_list[] = array(
			'value' => 'Size',
			'class' => null
		);
		$heading_list[] = array(
			'value' => 'View',
			'class' => null
		);
		$list->headings($heading_list);
		$list->row_class('ad');


		$link->url('http://www.projectwonderful.com/browse.php');
		$link->title('View this ad at Project Wonderful.');
		$link->action('view');

		foreach ( $wonderful_ad_list as $key => $val ) {

			$size_key = $val['width'].','.$val['height'];
			$size = $size_list[$size_key];
			$size ? $size : $size = 'Custom';

			$image-> src = (string)$val['thumbnail'];
			$image-> alt = $val['title'];
			$image-> style = 'max-height:200px;max-width:234px';

			$link-> query('adboxid='.$val['source_rel_id']);
//			$link-> tap('View'.' '.fa('external'));

			if ( $val['width'] && $val['height'] ) {
				$dimensions = $val['width'].' &times; '.$val['height'];
			}
			else {
				$dimensions = '(unknown dimensions)';
			}

			$wonderful_ad_list_content[] = array(
				$image-> paint(),
				$val['title'],
				$size.'<br/>'.$dimensions,
				$link->icon_link()
			);
		}
	}
}



$link-> url('?update_wonderful&amp;current_group=panel-wonderful');
$link-> tap('Get the latest data');
$link-> title('Download ad information from your PW account to Grawix. Do this any time you finish making changes at Project Wonderful.');

$wonderful_refresh_output  = '<br/><h3>Refresh ad data</h3>'."\n";
$wonderful_refresh_output .= '<p>Have you changed your bids at Project Wonderful? '.$link-> paint().'.</p>'."\n";

$link-> tap('Project Wonderful');
$link-> url('https://www.projectwonderful.com');
$link-> title('Visit this advertising service’s website.');

$wonderful_info_output .= '<br/><form accept-charset="UTF-8" action="ad.list.php" method="post">'."\n";
$wonderful_info_output .= '<p><label for="wonderful_id" class="instructions">Enter your '.$link-> paint().' account ID, or leave blank if you’re not a member.</label>'."\n";
$wonderful_info_output .= '<input type="text" name="wonderful_id" id="wonderful_id" size="4" style="width:5rem;" value="'.$wonderful_third_data['user_info'].'"/>'."\n";
$wonderful_info_output .= '<button class="btn primary save" name="submit" type="submit" value="save"><i></i>Save</button></p><br/>&nbsp;'."\n";
$wonderful_info_output .= '</form>'."\n";

$list-> content($wonderful_ad_list_content);

$wonderful_ad_list_output .= $list->format_headings();
$wonderful_ad_list_output .= $list->format_content();






///////// Build the Google Adsense panel.
if ( $ad_list ) {

	$link-> url('ad.adsense-edit.php');

	$edit_link-> url('ad.adsense-edit.php');
	$edit_link-> title('Edit this ad.');
	$edit_link-> action('edit');

	$delete_link-> url('ad.list.php');
	$delete_link-> title('Permanently delete this ad.');
	$delete_link-> action('delete');


	foreach ( $ad_list as $key => $val ) {
		if ( $val['source_id'] == 3 ) {

			$link-> query('ad_id='.$key);
			$link-> tap('ID '.$key);

			$delete_link-> query('delete_ad_id='.$key);
			$edit_link-> query('ad_id='.$key);

			$adlist_content[] = array (
				$link-> icon_link(),
				$delete_link->icon_link()."\n".$edit_link->icon_link()

			);
		}
	}
}

if ( $adlist_content ) {

	$heading_list = array(); // reset
	$heading_list[] = array(
		'value' => 'Ad ID',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Actions',
		'class' => null
	);
	$list->headings($heading_list);
	$list->row_class('ad');


	$final_adlist_content = array();

	$final_adlist_content = array_merge($final_adlist_content,$adlist_content);


	$list-> content($final_adlist_content);

	$adsense_list_output .= $list->format_headings();
	$adsense_list_output .= $list->format_content();

}
else {
	$adsense_list_output = $message->info_dialog('No Google Adsense ads found.');
}

$adsense_new_link = new GrlxLink;
$adsense_new_link-> url = 'ad.adsense-edit.php';
$adsense_new_link-> class = 'main-button right';
$adsense_new_link-> tap = '<i class="plus"></i>Create an AdSense ad';
$adsense_new_link-> title = 'Enter new AdSense code';
$adsense_list_output .= $adsense_new_link-> paint();






///////// Build the promos panel.

if ( $ad_list ) {

	$heading_list = array(); // reset
/*
	$heading_list[] = array(
		'value' => 'Mobile image',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Tablet image',
		'class' => null
	);
*/
	$heading_list[] = array(
		'value' => 'Image',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Destination URL',
		'class' => null
	);
	$heading_list[] = array(
		'value' => '&nbsp;',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Actions',
		'class' => null
	);

//	$promo_content = array();

	$list->headings($heading_list);
	$list->row_class('ad');

	$edit_link-> url('ad.promo-edit.php');
	$edit_link-> title('Edit this ad.');
	$edit_link-> action('edit');

	$delete_link-> url('ad.list.php');
	$delete_link-> title('Permanently delete this ad.');
	$delete_link-> action('delete');

	foreach ( $ad_list as $key => $val ) {
		if ( $val['source_id'] == 1 ) {
			if ( $val['tap_url'] ) {
				$url = $val['tap_url'];
			}
			else {
				$url = '-';
			}


			$size_key = $val['small_width'].','.$val['small_height'];
			$size = $size_list[$size_key];
			$size ? $size : $size = 'Custom';


			// Pretty much what it looks like.
/*
			$small_image = new GrlxImage;
			$small_image-> src = $val['small_image_url']; // The “source” attribute.
			$small_image-> alt = 'image '.$val['small_image_url']; // The “alternate text” attribute.

			$medium_image = new GrlxImage;
			$medium_image-> src = $val['medium_image_url']; // The “source” attribute.
			$medium_image-> alt = 'image '.$val['medium_image_url']; // The “alternate text” attribute.
*/


			$large_image = new GrlxImage;
			$large_image-> src = $val['large_image_url']; // The “source” attribute.
			$large_image-> alt = 'image '.$val['large_image_url']; // The “alternate text” attribute.

			$delete_link-> query('delete_ad_id='.$key);
			$edit_link-> query('ad_id='.$key);

			$promo_content[] = array (
/*
				$small_image->paint(),
				$medium_image->paint(),
*/
				$large_image->paint(),
//				$size.'<br/>'.$val['small_width'].' &times; '.$val['small_height'],
				$url,
				'&nbsp;',
				$delete_link->icon_link()."\n".$edit_link->icon_link()

			);
		}
	}
}




if ( $promo_content ) {

	$list->content($promo_content);
	$ad_list_output  = '<form accept-charset="UTF-8" action="ad.list.php" method="get">'."\n";
	$ad_list_output .= $list->format_headings();
	$ad_list_output .= $list->format_content();
	$ad_list_output .= '</form>'."\n";

}
else {
	$link-> url('ad.promo-create.php');
	$link-> tap('Create one now');
	$ad_list_output = $message->info_dialog('Huh. Looks like you haven’t uploaded any ads. '.$link-> paint().'.');
}

$link-> url('ad.promo-create.php');
$link-> tap('Create an ad');
$ad_list_output .= $link-> button_primary('new');








///////// Display the slots.

// Get all slots for this theme.
$slot_list = get_slots($current_theme['theme_id'],'ad',$db);

// How many ads are in each slot?
if ( $slot_list ) {
	foreach ( $slot_list as $key => $val ) {
		$ad_slot_match_list = get_ad_slot_matches($key,null,$db);
		$slot_list[$key]['ad_count'] = count($ad_slot_match_list);
	}
}

if ( $slot_list ) {

	$heading_list = array(); // reset
	$heading_list[] = array(
		'value' => 'Name',
		'class' => 'nudge'
	);
	$heading_list[] = array(
		'value' => 'Size',
		'class' => 'nudge'
	);
	$heading_list[] = array(
		'value' => 'Assigned ads',
		'class' => null
	);
/*
	$heading_list[] = array(
		'value' => 'Theme ID',
		'class' => null
	);
*/
	$heading_list[] = array(
		'value' => 'Actions',
		'class' => null
	);
	$list->headings($heading_list);
	$list->row_class('ad');

	$link->url('ad.slot-edit.php');
	$link->title('Control which ads appear in different places on your site.');
	$link->action('edit');

	foreach ( $slot_list as $key => $val ) {

		$link-> query('slot_id='.$key);

		$slot_list_content[$key] = array (
			$val['title'],
			$val['max_width'].' &times; '.$val['max_height'],
			$val['ad_count'],
//			$val['label'],
			$link->icon_link()
		);
	}
}

$list->content($slot_list_content);
$slot_list_output  = $list->format_headings();
$slot_list_output .= $list->format_content();









///////// Assemble the overall page view.

$view->page_title('Advertisements');
$view->tooltype('ad');
$view->headline("Advertisements");
$view->group_css('ad');

$view->group_h2('Ad slots');
$view->group_instruction('Slots are locations on your site’s pages that contain ads.');
$view->group_contents($slot_list_output);
$content_output .= $view->format_group().'<hr />';

$view->group_h2('House ads');
$view->group_instruction('A.k.a. promos, these are custom graphics and links you upload yourself.');
$view->group_contents($ad_list_output);
$content_output .= $view->format_group().'<hr />';

$view->group_h2('Project Wonderful');
$view->group_instruction('Ads served by this wonderful third-party service.');
$view->group_contents($wonderful_ad_list_output.$wonderful_info_output.$wonderful_refresh_output);
$content_output .= $view->format_group().'<hr />';

$view->group_h2('Google Adsense');
$view->group_instruction('Ads served by the internet juggernaut.');
$view->group_contents($adsense_list_output);
$content_output .= $view->format_group();


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
$output .= $content_output;

print($output);

?>

<div id="edit_modal" class="reveal-modal" data-reveal></div>

<?php
$output = $view->close_view();
print($output);
