<?php

/* Artists use this script to configure a theme tone.
 */

/*****
 * Setup
 */

require_once('panl.init.php');

$view = new GrlxView;
$modal = new GrlxForm_Modal;
$link = new GrlxLinkStyle;
$message = new GrlxAlert;
$form = new GrlxForm;
$theme = new GrlxXML_Theme;
$img = new GrlxImage;
$img-> db_new = $db;

$theme_id = numfunc_register_var('theme_id');
$tone_id = numfunc_register_var('tone_id');

$theme->set_theme_id($theme_id);
$theme->set_tone_id($tone_id);


/*****
 * Updates
 */

// Delete a tone
if ( is_numeric($_GET['delete_id']) ) {
	$delete_id = $_GET['delete_id'];
	$result = $db
		-> where('id', $delete_id)
		-> delete('theme_tone');
	if ( $result ) {
		$match_list = $db
			-> where('tone_id', $delete_id)
			-> delete('image_tone_match');
	}
	else {
		$alert_output .= $message->alert_dialog('Unable to delete tone from database.');
	}
	$result = $theme-> delete_tone_css($delete_id);
	if ( !$result ) {
		$alert_output .= $message->alert_dialog('Unable to delete tone CSS file.');
	}
}

// Duplicate a tone
if ( $_POST['modal-submit'] && $_POST['tone_title'] ) {
	// Fetch tone to dupe
	$cols = array(
		'title',
		'theme_id',
		'user_made',
		'value',
		'date_created'
	);
	$tone_meta = $db
		-> where('id', $tone_id)
		-> getOne('theme_tone', $cols);

	// Make some changes
	$tone_meta['title'] = $_POST['tone_title'];
	$tone_meta['user_made'] = 1;
	$tone_meta['date_created'] = $db->NOW();

	// Insert dupe
	$duped_id = $tone_id;
	$tone_id = $db-> insert('theme_tone', $tone_meta);

	// Dupe any image info
	$cols = array(
		'image_reference_id',
		'slot_id'
	);
	$match_list = $db
		-> where('tone_id', $duped_id)
		-> get('image_tone_match', null, $cols);
	if ( $match_list ) {
		foreach ( $match_list as $key => $val ) {
			$match_list[$key]['tone_id'] = $tone_id;
			$match_list[$key]['date_created'] = $db->NOW();
			$result = $db-> insert('image_tone_match', $match_list[$key]);
		}
	}

	// Dupe the tone file
	$result = $theme-> duplicate_tone_css($duped_id, $tone_id);
	if ( !$result ) {
		$alert_output .= $message->alert_dialog('Could not duplicate tone CSS file.');
	}
}

// Upload images
if ( $_FILES['slot_id'] && $tone_id ) {
	upload_multiple_files(DIR_THEME_IMG);
	foreach ( $_FILES['slot_id']['name'] as $slot_id => $filename ) {
		if ( $filename <> '' ) {
			$filepath = DIR_THEME_IMG.'/'.$filename;
			if ( substr($filepath, 0, 2) == '..' ) {
				$x = mb_strlen($filepath,"UTF-8");
				$filepath = mb_substr($filepath, 2, $x, "UTF-8");
			}
			$ref_id = $img-> check_image_ref($filepath);
			if ( !isset($ref_id) ) {
				$data = array(
					'url' => $filepath,
					'description' => $filename,
					'date_created' => $db-> NOW()
				);
				$ref_id = $db-> insert('image_reference', $data);
			}
			$match = array(
				'image_reference_id' => $ref_id,
				'date_modified' => $db->NOW()
			);
			$result = $db
				-> where('tone_id', $tone_id)
				-> where('slot_id', $slot_id)
				-> update('image_tone_match', $match);
		}
	}
}

// Save changes to the tone
if ( $_POST['submit'] ) {
	$values_list = $_POST['input'];
	$new_xml = $theme-> build_value_xml($values_list);
	$data = array('value' => $new_xml, 'date_modified' => $db-> NOW());
	$result = $db
		-> where('id', $tone_id)
		-> update('theme_tone', $data);
	if ( $db-> count <= 0 ) {
		$alert_output .= $message->alert_dialog('Tone changes failed to save.');
	}
	else {
		// Save the tone file *after* we pull the info from the db again
		$compile_tone = true;
	}
}


/*****
 * Display logic
 */

$theme_info = $theme->edit_prep();

if ( $theme_id && $tone_id ) {

}

// Prep tone options for display on this page
if ( $theme_info['value_map'] && $theme_info['value'] ) {
	$options_list = $theme-> prep_options_for_editing($tone_values, $tone_map);

}

// Build the tone CSS file
/*
if ( $compile_tone && $options_for_css ) {
	$theme-> tone_id = $tone_id;
	$theme-> tone_css_list = $options_for_css;
	$css_list = $theme-> build_tone_css_list();
	$css_file = $theme-> compile_tone_css($css_list);

	if ( $css_file ) {
		$alert_output = $message->success_dialog('Tone CSS file saved.');
	}
	else {
		$alert_output = $message->alert_dialog('Unable to save tone CSS file.');
	}
}*/

if ( ($theme_info['url'] !== null) && ($theme_info['url'] != 'None listed.') ) {
	if ( substr($theme_info['url'], 0, 7) != 'http://' ) {
		$theme_info['url'] = 'http://'.$theme_info['url'];
	}
	$theme_info['url'] = '<a href="'.$theme_info['url'].'">'.$theme_info['url'].'</a>';
}

// Output for theme meta
$meta_list = array(
	'Description' => $theme_info['description'],
	'Author' => $theme_info['author'],
	'URL' => $theme_info['url'],
	'Version' => $theme_info['version'],
	'Installed' => $theme_info['date']
);

// Add actions for this tone
if ( $theme_info['user_made'] == 1 ) {
	$link->url($_SERVER['SCRIPT_NAME']);
	$link->query("theme_id=$theme_id&delete_id=$tone_id");
	$link->tap('Delete');
	$tone_action_output .= $link->button_tertiary('delete');
}

$link->url('site.theme-dupetone.ajax.php');
$link->reveal(true);
$link->query("theme_id=$theme_id&tone_id=$tone_id");
$link->tap('Create new');
$tone_action_output .= $link->button_secondary('new');

// Build a list of all tones for this theme
$cols = array('id', 'title');
$result = $db
	-> where('theme_id', $theme_id)
	-> orderBy('title', 'ASC')
	-> get('theme_tone', NULL, $cols);

if ( $result ) {
	foreach ( $result as $item ) {
		$tone_list[$item['id']] = ucfirst($item['title']);
	}
}

if ( count($tone_list) > 1 ) {

	$tone_menu_output  = '<a title="Choose another tone." class="btn secondary tone" data-options="align:right" data-dropdown="drop-items"><i></i></a>';
	$tone_menu_output .= '<ul id="drop-items" data-dropdown-content>';
	foreach ( $tone_list as $key => $val ) {
		if ( $key != $tone_id ) {
			$tone_menu_output .= '<li><a href="?theme_id='.$theme_id.'&tone_id='.$key.'">'.$val.'</a></li>';
		}
	}
	$tone_menu_output .= '</ul>';
}
$tone_title_output = 'Tone <span>'.$theme_info['tone_title'].'</span>'.$tone_menu_output;

// Organize for display based on type
if ( $options_list ) {
	foreach ( $options_list as $type => $set ) {
		if ( $type == 'color' ) {
			$palette_list = $set;
			unset($options_list[$type]);
		}
	}
}

if ( $palette_list ) {
	$view->group_h3('Palette');
	$view->group_css('tone colors');

	$palette_output = '<ul class="option-list">';
	foreach ( $palette_list as $label => $val ) {
		unset($this_option);
		$this_label = ucfirst($val['title']);
		$this_option = '<input type="text" id="color-'.$label.'" name="input['.$label.']" value="'.$val['value'].'" />';
		$palette_output .= '<li><h5><span>'.$this_option.'</span>'.$this_label.'</h5></li>';
		$js_call .= "$('#".$label."').css('background-color', '".$val['value']."')\n";
	}
	$palette_output .= '</ul></div>';

	$l = new GrlxLayout;
	$l-> filler = '&nbsp;';
	$layout_output = '<div class="palette">'.$l-> decode($theme_info['palette']);

	$view->group_contents($palette_output.$layout_output);
	$palette_output = $view->format_group().$form->form_buttons().'<hr class="sub"/>';
}

if ( $options_list ) {
	$view->group_h3('Styles');
	$view->group_css('tone');

	$styles_output = '<ul class="option-list">';
	$x = 1;
	foreach ( $options_list as $type => $set ) {
		foreach ( $set as $label => $val ) {
			unset($this_option);
			$this_label = ucfirst($val['title']);
			if ( $val['type'] == 'border-style' ) {
				$this_option = build_select_val_as_key("input[$label]", array('none','hidden','dotted','dashed','solid','double','groove','ridge'), $val['value']);
			}
			if ( $val['type'] == 'border-width' ) {
				$this_option = '<input type="text" name="input['.$label.']" value="'.$val['value'].'" />';
			}
			if ( $val['type'] == 'link-decoration' ) {
				$this_option = build_select_val_as_key("input[$label]", array('none','underline','overline','line-through'), $val['value']);
			}
			$styles_output .= '<li><h5><span class="shrink">'.$this_option.'</span>'.$this_label.'</h5></li>';
			$x++;
		}
	$styles_output .= '</ul>';

	$view->group_contents($styles_output);
	$styles_output = $view->format_group().$form->form_buttons().'<hr class="sub"/>';
	}
}

if ( $slot_list ) {
	$view->group_h3('Images');
	$view->group_css('tone');

	foreach ( $slot_list as $slot_id => $val ) {
		$this_option = '<input type="file" name="slot_id['.$slot_id.']"/>';
		$this_label = ucfirst($val['title']).' <small>max dimensions: '.$val['max_width'].'&times;'.$val['max_height'].'</small>';
		$this_image = $val['url'];
		$slot_output .= '<div class="slot">';
		$slot_output .= '<h5>'.$this_label.'</h5><div>'.$this_option.'</div>';
		if ( isset($this_image) ) {
			$slot_output .= '<img class="preview" src="'.$this_image.'"/>';
		}
		else {
			$slot_output .= '<span class="no-image">no image</span>';
		}
		$slot_output .= '</div>';
	}
	$view->group_contents($slot_output);
	$slot_output = $view->format_group().$form->form_buttons();
}

if ( $options_list ) {
	$view->tooltype('tone');
	$view->headline($tone_title_output);
	$view->action($tone_action_output);

	$form->multipart(true);
	$form->send_to($_SERVER['SCRIPT_NAME']);
	$form_output  = $view->view_header();
	$form_output .= $form->open_form();

	$form->input_hidden('theme_id');
	$form->value($theme_id);
	$form_output .= $form->paint();
	$form->input_hidden('tone_id');
	$form->value($tone_id);
	$form_output .= $form->paint();

	$form_output .= $palette_output;
	$form_output .= $styles_output;
	$form_output .= $slot_output;
	$form_output .= $form->close_form();
}
// Theme with no panel-editable options
else {
	$form_output = <<<EOL
	<div class="panel">
		<h3>No tone options</h3>
		<p>This theme does not contain panel-editable options. You’ll have to edit the CSS directly.</p>
	</div>
EOL;
}

$view->page_title("Theme: $theme_info[title]");
$view->tooltype('tone');
$view->prepend_stylesheet('spectrum.css');
$view->headline("Theme <span>$theme_info[title]</span>");

$link->url('site.theme-manager.php');
$link->tap('Back to list');
$action_output = $link->text_link('back');
$view->action($action_output);

$view->meta_css('theme');
$view->meta_preview('<img src="http://placehold.it/500x100/ccc/000.png&text=preview_image" />');
$view->meta_info_list($meta_list);
$meta_output = $view->format_meta().'<hr />';


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
$output .= $modal->modal_container();
$output .= $meta_output;
$output .= $form_output;
print($output);

$js_call .= <<<EOL
	$( '[id^="color-"]' ).spectrum({
		preferredFormat: "hex",
		showPalette: true,
		showInitial: true,
		showInput: true,
		showSelectionPalette: true,
		palette: [ ],
		localStorageKey: "grawlix.themecolors",
		change: function(color) {
			var id = $(this).attr('id').substring(6); // trim 'color-' off the sting
			var setHex = color.toHexString();
			$( '#'+id ).css('background-color', setHex);
		},
	});
EOL;

$view->add_script('spectrum.min.js');
$view->add_inline_script($js_call);
print ( $view->close_view() );
