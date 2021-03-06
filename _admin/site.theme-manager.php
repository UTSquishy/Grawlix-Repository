<?php

/* Artists use this script to manage their site themes.
 */

/* ! Setup * * * * * * * */

require_once('panl.init.php');

$args['yah'] = 9;
$view = new GrlxView($args);
$form = new GrlxForm;
$button = new GrlxLinkStyle;
$sl = new GrlxSelectList;


/* ! Actions * * * * * * * */

// Change one or more tone_id records
if ( is_numeric($_POST['new_tone_id']) && $_POST['sel'] && $_POST['submit'] ) {
	unset($_GET);
	$new_id = $_POST['new_tone_id'];

	if ( is_array($_POST['sel']) ) {
		$sel = array_keys($_POST['sel']);
		foreach ( $sel as $item ) {
			$db_vars[] = strfunc_split_tablerow($item);
		}
	}
	else {
		$db_vars[] = strfunc_split_tablerow($_POST['sel']);
	}

	if ( $db_vars ) {
		foreach ( $db_vars as $i=>$row ) {
			$row['table'] == 'milieu' ? $col = 'value' : $col = 'tone_id';
			$data = array($col=>$new_id);
			if ( $row['table'] == 'book_page' ) {
				$marker = new GrlxMarker($row['id']);
				$page_ids = array_keys($marker->pageList);
				$db->where('id',$page_ids,'IN');
				unset($marker);
			}
			else {
				$db->where('id',$row['id']);
			}
			$db->update($row['table'],$data);
		}
	}
}

// Install a theme
if ( $_GET['install'] ) {
	$dir = urldecode($_GET['install']);
	$args['action'] = 'install';
	$args['dirName'] = $dir;
}

// Install tones for a given theme
if ( is_numeric($_GET['id']) && $_GET['dir'] && $_GET['addtone'] ) {
	$dir = urldecode($_GET['dir']);
	$list = urldecode($_GET['addtone']);
	$args['action'] = 'addtone';
	$args['themeID'] = $_GET['id'];
	$args['dirName'] = $dir;
	$args['toneList'] = $list;
}

// Change the multi-theme setting
if ( $_GET['toggle-multi'] ) {
	$args['action'] = 'toggle-multi';
}

$theme = new GrlxTheme($args);

if ( $theme->errorOutput ) {
	$errorMsg = new GrlxAlert;
//	$alert_output = $errorMsg->alert_dialog($theme->errorOutput);
}

if ( $theme->successOutput ) {
/*
	$successMsg = new GrlxAlert;
	$alert_output .= $successMsg->success_dialog($theme->successOutput);
*/
}

if ( $theme->toInstallOutput ) {
	$message = new GrlxAlert;
//	$alert_output .= $message->info_dialog($theme->toInstallOutput);
}

// Assignment section
$button->url($_SERVER['SCRIPT_NAME']);
$button->query('toggle-multi=true');

$sl->setName('new_tone_id');
$sl->setList($theme->toneSelectList);
$sl->setValueID('id');
$sl->setValueTitle('title');

if ( $theme->multiTone < 1 ) {

	$button->title('With this you can set a different theme or tone for nearly every page of your site.');
	$button->tap('Turn on multi-theme');
	$action_output = $button->button_secondary('power');

	$instruction = 'By default, your site uses one theme &amp; tone. Choose it here.';
	$label_output = 'Site theme &amp; tone';
	$form->input_hidden('sel');
	$form->value('milieu-'.$theme->milieuID['tone']);
	$list_output = $form->paint();

	$sl->setCurrent($theme->defaultToneID);
	$tone_select = $sl->buildSelect();
}
else {

	$button->title('Serve one theme/tone to your entire site.');
	$button->tap('Turn off multi-theme');
	$action_output = $button->button_tertiary('power');

	$instruction = 'Multi-theme allows you to choose a unique setting for every group of pages listed here.';
	$label_output = 'Apply to selection';

	// Get book sections
	$list = new GrlxList;
	$list->draggable(false);
	$heading_list[] = array(
		'value' => 'Select',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Group',
		'class' => 'nudge'
	);
	$heading_list[] = array(
		'value' => 'Theme/tone',
		'class' => 'nudge'
	);
	$list->row_class('theme');
	$list->headings($heading_list);
	$list_output = $list->format_headings();
	$book = new GrlxComicBook;
	$book->getMarkerList();
	// Chapters
	if ( $book->markerList ) {
		$i = 0;
		foreach ( $book->markerList as $marker_id=>$info ) {
			$i++;
			$row_select = '<input type="checkbox" name="sel[book_page-'.$marker_id.']" />';
			$row_chapter = $info['type'].' '.$i.' <span class="title">'.$info['title'].'</span>';
			$row_theme = $theme->toneSelectList[$info['tone_id']]['title'];
			$group_list[] = array($row_select,$row_chapter,$row_theme);
		}
	}
	// Book archives
	if ( $book->info ) {
		$row_select = '<input type="checkbox" name="sel[book-'.$book->info['id'].']" />';
		$row_book = 'Archives <span class="title">'.$book->info['title'].'</span>';
		$row_theme = $theme->toneSelectList[$book->info['tone_id']]['title'];
		$group_list[] = array($row_select,$row_book,$row_theme);
	}
	// Static pages
	$static = new GrlxStaticPage;
	$page_list = $static->getPageList();
	if ( $page_list ) {
		foreach ( $page_list as $id=>$info) {
			$row_select = '<input type="checkbox" name="sel[static_page-'.$id.']" />';
			$row_page = 'Static page <span class="title">'.$info['title'].'</span>';
			$row_theme = $theme->toneSelectList[$info['tone_id']]['title'];
			$group_list[] = array($row_select,$row_page,$row_theme);
		}
	}

	$sl->setCurrent(0);
	$tone_select = $sl->buildSelect();
	$list->content($group_list);
	$list_output .= $list->format_content().'<br/>';
}

$form->send_to($_SERVER['SCRIPT_NAME']);
$assign_output  = $form->open_form();
$assign_output .= $list_output;
$assign_output .= '<div class="row form widelabel">';
$assign_output .= '<div><label>'.$label_output.'</label></div>';
$assign_output .= '<div>'.$tone_select.'</div>';
$assign_output .= '</div>';
$assign_output .= $form->form_buttons();
$assign_output .= $form->close_form();

// List of installed themes
if ( $theme->outputList ) {
	$all_themes_output = '<ul id="themes" class="small-block-grid-1 medium-block-grid-2 large-block-grid-3">';
	foreach ( $theme->outputList as $id=>$info ) {
		if ( $info['preview'] ) {
			$preview = '<img src="'.$info['preview'].'" />';
		}
		if ( $info['action'] ) {
			$box_css = ' off';
			$action = '<a class="'.$info['action'].'" href="?'.$info['action'].'='.$info['directory'].'">'.ucfirst($info['action']).' theme</a>';
		}
		else {
			$box_css = '';
			$action = null;
			$new = 0;
			$missing = 0;
			if ( $info['tones'] ) {
				foreach ( $info['tones'] as $label=>$tone_info ) {
					if ( $tone_info['action'] == 'install' && $tone_info['options'] ) {
						$new++;
						$new_list[] = $tone_info['options'];
					}
					if ( $tone_info['action'] == 'missing' ) {
						$missing++;
						$missing_list[] = $tone_info['options'];
					}
				}
				if ( $new > 0 ) {
					$new = qty('tone',$new);
					$new_list = implode('||', $new_list);
					$action .= '<a class="install" href="?id='.$info['id'].'&amp;dir='.$info['directory'].'&amp;addtone='.$new_list.'">Install '.$new.'</a>';
				}
				if ( $missing > 0 ) {
/*
					$missing = qty('tone',$missing);
					$missing_list = implode('||', $missing_list);
					$action .= '<a class="missing" href="?id='.$info['id'].'&amp;dir='.$info['directory'].'&amp;missing='.$missing_list.'">Missing '.$missing.'</a>';
*/
				}
			}
		}
		$all_themes_output .= <<<EOL
			<li id="theme-$id">
				<div class="box$box_css">
					$preview
					<h4>$info[title]</h4>
					<div class="actions">
						$action
					</div>
				</div>
			</li>
EOL;
	}
	$all_themes_output .= '</ul>';
}

$view->page_title('Themes');
$view->tooltype('theme');
$view->headline('Themes');
$view->action($action_output);
$view->group_css('theme');
$view->group_h2('Assign');
$view->group_instruction($instruction);
$view->group_contents($assign_output);
$content_output .= $view->format_group().'<hr/>';
$view->group_h3('List');
$view->group_css('theme');
$view->group_instruction('Manage your installed themes here.');
$view->group_contents($all_themes_output);
$content_output .= $view->format_group();


/* ! Display * * * * * * * */

$output  = $view->open_view();
$output .= $view->view_header();
//$output .= '<div id="result"></div>';
$output .= $alert_output;
$output .= $content_output;

/*
$js_call = <<<EOL
	$('a[href="#"]').click(function(e){
		e.preventDefault();
		var item = $(this).attr('id');
		var tone_id = $('select option:selected').val();
		$.ajax({
			url: "ajax.tone-set.php",
			data: "item=" + item + "&tone_id=" + tone_id,
			dataType: "html",
			success: function(data){
				$('#result').html(data);
			}
		});
	});
EOL;

	$('[id^="id-"]').click(function(){
		var item = $(this).attr('id'); // id of the item to change
		var parent = $('#'+item).parent().parent().parent().attr('class'); // the class contains current visibility of item
		$.ajax({
			url: "ajax.tone-set.php",
			data: "var=" + item + "&class=" + parent,
			dataType: "html",
			success: function(data){
				$('#result').html(data);
			}
		});
	});
*/
//$view->add_inline_script($js_call);
$output .= $view->close_view();
print($output);

/* OLD CODE

$fileops = new GrlxFileOps;
$theme_and_tone_list = $db_ops->get_theme_and_tone_list('tone_id');
$milieu_id = $db_ops->get_site_tone_milieu_id();
$site_tone_id = $db_ops->get_site_tone_id();
$chapter_and_tone_list = $db_ops->get_chapter_and_tone_list();

	// Currently assigned themes
	$list = new GrlxList;
	$list->row_class('theme');
	$list->draggable(false);
	$headings = array(
		'Section',
		'Theme',
		'Tone',
		'Actions'
	);
	$list->headings($headings);
	$active_output = $list->format_headings();

//	$link->url('ajax.tone-set.php');
//	$link->reveal(true);

	if ( $site_tone_id ) {
		$this_id = $site_tone_id;
		$this_tone = $theme_and_tone_list[$this_id]['tone'];
		$this_theme = $theme_and_tone_list[$this_id]['theme'];
		$this_title = urlencode('Site theme');
//		$link->query('milieu_id='.$milieu_id.'&tone_id='.$site_tone_id.'&title='.$this_title);
//		$actions = $link->icon_link('edit');

		$this_list[$milieu_id] = array(
			'Non-comic pages',
			$this_theme,
			$this_tone,
			$actions
		);
		$list->content($this_list);
		$active_output .= $list->format_content();
		unset($this_list);
	}

	if ( $chapter_and_tone_list ) {
		foreach ( $chapter_and_tone_list as $comic_title => $array ) {
			foreach ( $array as $chapter_id => $val ) {
				$this_id = numfunc_check_empty($val['tone_id']);
				$this_tone = strfunc_check_empty($theme_and_tone_list[$this_id]['tone']);
				$this_theme = strfunc_check_empty($theme_and_tone_list[$this_id]['theme']);
				$this_title = urlencode($val['chapter_title']);
//				$link->query("chapter_id=$chapter_id&tone_id=$this_id&title=$this_title");
//				$actions = $link->icon_link('edit');

				$this_list[$chapter_id] = array(
					$val['chapter_title'],
					$this_theme,
					$this_tone,
					$actions
				);
			}
			$list->content($this_list);
			$active_output .= $list->format_content();
			unset($this_list);
		}
	}

*/