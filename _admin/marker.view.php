<?php

/*****
 * Setup
 */

require_once('panl.init.php');

$view = new GrlxView;
//$modal = new GrlxForm_Modal;
$message = new GrlxAlert;
$link = new GrlxLinkStyle;
$list = new GrlxList;
$form = new GrlxForm;
$fileops = new GrlxFileOps;
$fileops->db = $db;
$comic_image = new GrlxComicImage;
$sl = new GrlxSelectList;

$form->send_to($_SERVER['SCRIPT_NAME']);


$var_list = array(
	'marker_id','book_id','new_order'
);
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}

if ( !$marker_id ) {
	header('location:book.view.php');
	die();
}

$marker = new GrlxMarker($marker_id);

$new_order ? $new_order : $new_order = 1;




/*****
 * Updates
 */


if ( $_POST['new_sort_order'] && $book_id ) {
	foreach ( $_POST['new_sort_order'] as $key => $val ) {
		if ( $_POST['orig_sort_order'][$key] > $_POST['new_sort_order'][$key] ) {
			$val -= 0.0001;
			$marker-> movePage($key,$val);
		}
		elseif ( $_POST['orig_sort_order'][$key] < $_POST['new_sort_order'][$key] ) {
			$val += 0.0001;
			$marker-> movePage($key,$val);
		}
	}
	reset_page_order($book_id,$db);
}


if ( $_FILES && $book_id ){

	$which = 'file';
	$fileops-> up_set_destination_folder('../'.DIR_COMICS_IMG);
	$files_uploaded = $fileops-> up_process($which);

	if ( $files_uploaded ) {

		// Count which page sort_order to add each new page.
		$i = 0;

		foreach ( $files_uploaded as $key => $val ) {

			// Create the image DB record.
			$new_image_id = $comic_image-> createImageRecord ( DIR_COMICS_IMG.'/'.$val );

			// Create the page DB record.
			$title = explode('.', $val);
			$title = $title[0];
			if ( strpos($title, '/')) {
				$title = explode('/', $title);
				$title = $title[1];
			}
			$new_page_id = $comic_image-> createPageRecord($title,$new_order - 1 + $i,$book_id);
//			$first_page_id ? null : $first_page_id = $new_page_id;

			// Assign the image to the page.
			if ( $new_image_id && $new_page_id ) {
				$new_assignment_id = $comic_image-> assignImageToPage($new_image_id,$new_page_id);
			}

			$i+=0.001;
		}
		reset_page_order($book_id,$db);

		if ( count($files_uploaded) == 1 ) {
			$alert_output .= $message->success_dialog('One image added. Make changes below or <a href="book.view.php">check out all the pages</a>.');
		}
		if ( count($files_uploaded) > 1 ) {
			$alert_output .= $message->success_dialog(count($files_uploaded).' images added. Make changes below or <a href="book.view.php">check out all the pages</a>.');
		}
	}
	if ( !$files_uploaded ) {
//		$alert_output .= $message->alert_dialog('No images added.');
	}
}





/*****
 * Display logic
 */

if ( !is_writable('../'.DIR_COMICS_IMG) ) {
	$alert_output .= $message->alert_dialog('The comics images folder is not writable.');
}


// Reset the marker info after making updates.
if ( $_POST ) {
	$marker = new GrlxMarker($marker_id);
}




$marker_type_list = $db-> get ('marker_type',null,'id,title');
$marker_type_list = rekey_array($marker_type_list,'id');


if ( $marker-> pageList ) {

	$edit_link = new GrlxLinkStyle;
	$edit_link->url('book.page-edit.php');
	$edit_link->title('Edit page meta.');
	$edit_link->reveal(false);
	$edit_link->action('edit');

	$delete_link = new GrlxLinkStyle;
	$delete_link->i_only(true);
	
	$list-> draggable(false);
	$list->row_class('chapter');

/*
	$heading_list[] = array(
		'value' => 'Select',
		'class' => null
	);
*/
	$heading_list[] = array(
		'value' => ' ',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Title',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Page number',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Date',
		'class' => null
	);
	$heading_list[] = array(
		'value' => 'Actions',
		'class' => null
	);
	
	$list->headings($heading_list);


	$pages_displayed = 0;
	foreach ( $marker-> pageList as $key => $val ) {

		$page = new GrlxComicPage($val['id']);

		$delete_link->id("id-$key");
		$edit_link->query("page_id=$key");
		$actions = $delete_link->icon_link('delete').$edit_link->icon_link();
/*
		$link->url('book.page-edit.php');
		$link->query('page_id='.$key);
		$link->tap(qty('page',$val['page_count']));
		$pg_label = $link->arrow_link();
*/

		$link_output = $link->icon_link('edit');
		$val['title'] ? $title = $val['title'] : $title = '<span class="error">Untitled</span>';
		$select = '<input type="checkbox" name="sel['.$key.']" value="'.$key.'"/>'."\n";

		$list_items[$key] = array(
//			'select'=> $select,
			'select' => '&nbsp;',
			'title'=> $title,
			'sort_order'=> '<input type="number" name="new_sort_order['.$key.']" value="'.intval($val['sort_order']).'" style="width:3rem"/>',
			'date'=> format_date($val['date_publish']),
			'action'=> $actions
		);
		$orig_output .= '<input type="hidden" name="orig_sort_order['.$key.']" value="'.$val['sort_order'].'"/>'."\n";
		$pages_displayed++;
	}

	$list->content($list_items);
	$content_output  = $list->format_headings();
	$content_output .= $list->format_content();
}

if ( $marker_type_list ) {
	$sl-> setName('add-marker-type');
	$sl-> setList($marker_type_list);
//	$sl-> setCurrent();
	$sl-> setValueID('id');
	$sl-> setValueTitle('title');
	$select_options = $sl-> buildSelect();

/*
	$select_options .= '<select name="add-marker-type" style="width:12rem">';
	foreach ( $marker_type_list as $key => $val ) {
		$select_options .= '<option value="'.$key.'">'.$val['title'].'</option>';
	}
	$select_options .= '</select>';
*/
}

/////// Add these later

$add_marker_output = <<<EOL
Type:<br/>
$select_options<br/>
Title: <input type="text" name="new-marker-title" id="new-marker-title" value="" style="width:20rem" width="40" /><br/>
<button class="btn primary new" name="new-marker" type="submit"value="add-marker"><i></i>Add marker(s)</button>

EOL;
/*
$delete_marker_output = <<<EOL
<button class="btn secondary delete" name="delete-marker" type="submit" value="delete-marker"><i></i>Remove marker(s)</button>

EOL;
*/

$link->url('book.pages-create.php');
$link->tap('bulk importer');

$new_upload_field  = '<input name="file[]" id="file" type="file" multiple /><br/>';
$new_upload_field .= '<button class="btn primary new" name="new-pages" type="submit" value="new-pages"><i></i>Add pages</button>';
$new_upload_field .= '<br/>&nbsp;<p>'.number_format($fileops-> up_get_max_size()).' bytes max file size. (Recommended max: 100,000 bytes per image.) The server can accept up to '.$fileops-> up_get_max_file_uploads().' images at a time.</p>'."\n";
$new_upload_field .= '<p>Uploading more than '.$fileops-> up_get_max_file_uploads().' images? Try the '.$link-> paint().'.</p>';



$type = $marker_type_list [ $marker-> markerInfo['marker_type_id'] ]['title'];
$type ? $type : $type = 'Marker';
$view->page_title($type.': '.$marker-> markerInfo['title']);
$view->tooltype('page');
$view->headline($type.' <span>'.$marker-> markerInfo['title'].'</span>');

$link->url('marker.edit.php?marker_id='.$marker-> markerID);
$link->tap('Edit '.$marker_type_list [ $marker-> markerInfo['marker_type_id'] ]['title'].' info');
$link->reveal(true);
$action_output = $link->text_link('editmeta');


/////// Add later
/*
$link->url('marker.create.php?at_page='.$first_page_id);
$link->tap('Insert marker');
$link->reveal(false);
$action_output .= $link->button_secondary('new');
*/
$view->action($action_output);



/*
$view->group_h2('Add marker');
$view->group_instruction('INSTRUCTIONS GO HERE');
$view->group_contents($add_marker_output);
$content_output .= '<hr/>' . $view->format_group();
*/

/*
$view->group_h2('Remove marker');
$view->group_instruction('INSTRUCTIONS GO HERE');
$view->group_contents($delete_marker_output);
$content_output .= $view->format_group().'<hr/>';
*/

$view->group_h2('Add pages');
$view->group_instruction('Upload images to create new pages here.');
$view->group_contents($new_upload_field);
$upload_output .= $view->format_group();


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
//$output .= $modal->modal_container();


$output .= '<form accept-charset="UTF-8" method="post" action="marker.view.php" enctype="multipart/form-data">'."\n";
if ( $pages_displayed && $pages_displayed > 10 ) {
	$output .= $upload_output.'<hr/>';
	$output .= $content_output;
}
else {
	$output .= $content_output.'<hr/>';
	$output .= $upload_output;

}
$output .= '<input name="new_order" id="new_order" type="hidden" value="'.($marker-> endPage+1).'" />';
$output .= '<input name="marker_id" id="marker_id" type="hidden" value="'.$marker_id.'" />';
$output .= '<input name="book_id" id="book_id" type="hidden" value="'.$marker-> markerInfo['book_id'].'" />';
$output .= $orig_output;
$output .= '</form>'."\n";

print($output);


$js_call = <<<EOL
	$( "i.sort" ).hover( // highlight a draggable row
		function() {
			$( this ).parent().parent().addClass("dragging");
		}, function() {
			$( this ).parent().parent().removeClass("dragging");
		}
	);
	$( "a.edit" ).hover( // highlight the editable item
		function() {
			$( this ).parent().parent().addClass("editme");
		}, function() {
			$( this ).parent().parent().removeClass("editme");
		}
	);
	$( "i.delete" ).hover( // highlight a row to be deleted
		function() {
			$( this ).parent().parent().addClass("red-alert");
		}, function() {
			$( this ).parent().parent().removeClass("red-alert");
		}
	);
	$( '[id^="id-"]' ).click( // delete item
		function() { // update the db
			var item = $(this).attr('id'); // id of the item to delete
			var container = $('#'+item).parent().parent();
			$.ajax({
				url: "ajax.book-delete.php",
				data: "delete-chapter=" + item,
				dataType: "html",
				success: function(data){
					$(container).remove();
					renumberOrder( '[id^="sort-"]', 1 );
				}
			});
		}
	);
	$( "#sortable" ).sortable({ // sort items
		activate: function(event, ui) { // highlight the dragged item
			$( ui.item ).children().addClass("dragging");
		},
		deactivate: function(event, ui) { // turn off the highlight
			$( ui.item ).children().removeClass("dragging");
			renumberOrder( '[id^="sort-"]', 1 );
		},
		update: function() {
			serial = $('#sortable').sortable('serialize');
			$.ajax({
				url: "ajax.sort.php",
				type: "post",
				data: serial,
				success: function(data){
					var obj = jQuery.parseJSON(data);
				},
				error: function(){
					alert("AJAX error");
				}
			});
		}
	});
	$( "#sortable" ).disableSelection();
EOL;


$view->add_jquery_ui();
$view->add_inline_script($js_call);
print( $view->close_view() );
