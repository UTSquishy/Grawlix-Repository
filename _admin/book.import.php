<?php

class bulkImport{
	var $import_top_list;

	function makeSerial(){
		$serial = date('YmdHis').substr(microtime(),2,6);
		return $serial;
	}
	function moveImage($source_path,$serial){
		mkdir('../'.DIR_COMICS_IMG.'/'.$serial);
		$success = rename ( $source_path, '../'.DIR_COMICS_IMG.'/'.$serial.'/'.$this_file );
		return $success;
	}
	function importFolders(){
		if ( is_array($this-> fileList) ) {
			foreach ( $this-> fileList as $item ) {
				
			}
		}
	}
}

$bimport = new bulkImport;


/*****
 * Setup
 */

require_once('panl.init.php');

$view = new GrlxView;
$fileops = new GrlxFileOps;
$message = new GrlxAlert;
$book = new GrlxComicBook(1);
$comic_image = new GrlxComicImage;
$marker = new GrlxMarker;
$link = new GrlxLinkStyle;
$link1 = new GrlxLinkStyle;
$list = new GrlxList;

// Yeeeeah, really should make this a constant.
$import_path = '../import';

if ( $book ) {
	$book-> getPages();
}

if ( $book-> pageList ) {
	$last_page = end($book-> pageList);
	$last_page = $last_page['sort_order'];
}
else {
	$last_page = 0;
}

$book_id = $book-> bookID;

$marker_type_list = $db-> get ('marker_type',null,'id,title');
$marker_type_list = rekey_array($marker_type_list,'id');







/*****
 * Actions
 */


if ( $_POST ) {

	// What’s in the import folder?
	$import_top_list = $fileops-> get_dir_list($import_path);

	if ( $import_top_list ) {
		foreach ( $import_top_list as $key => $val ) {

			if (is_dir($import_path.'/'.$val)) {
				$folder_list[$val] = $import_path.'/'.$val;
			}
			else {
				$file_list[$val] = $import_path.'/'.$val;
			}
		}
	}

	// Build a list of each folder and its contents.
	if ( $folder_list ) {
		foreach ( $folder_list as $key => $val ) {
			$file_list = $fileops-> get_dir_list($val);
			if ( $file_list && count($file_list) > 0 ) {
				$master_folder_list[$key] = $file_list = $fileops-> get_dir_list($val);
			}
		}
	}

	if ( $master_folder_list ) {

		// Assume everything works unless proven otherwise. 
		// I feel optimistic. Thanks, @BarefootCoffee!
		$total_success = true;

		$i = $last_page + 1; // Sort_order count
		$first_page_id = null; // Triggers when to create a marker.

		foreach ( $master_folder_list as $folder => $file_list ) {

				// Create the marker. We use the folder’s name as the marker’s title.
				// TO DO: Make the marker type dynamic.
				$new_marker_id = $marker->createMarker($folder,1,$first_page_id);

				// We’re on the first page of this set.
				$first_page = true;

				foreach ( $file_list as $this_file ) {

					$serial = $bimport-> makeSerial();
					$permissions = fileperms($import_path.'/'.$folder);

					if ( $permissions == '16895' ) {
						$new_path = '../'.DIR_COMICS_IMG.'/'.$serial;
						mkdir($new_path);
						$success = rename ( $import_path.'/'.$folder.'/'.$this_file, $new_path.'/'.$this_file );
					}
					else {
						$success = false;
						$total_success = false;
						$alert_output = $message-> alert_dialog('I couldn’t import all of the new images. Looks like a permissions error. Please temporarily set the folders in /import to 777.');
					}

					if ( $success ) {

						// Create the image DB record.
						$new_image_id = $comic_image-> createImageRecord ( '/'.DIR_COMICS_IMG.$serial.'/'.$this_file );

						// Create the page DB record.
						$title = explode('.',$this_file);
						$title = $title[0];
						if ( $first_page === true ) {
							$new_page_id = $comic_image-> createPageRecord($title,$last_page + $i,$book_id,$new_marker_id);
							$first_page = false;
						}
						else {
							$new_page_id = $comic_image-> createPageRecord($title,$last_page + $i,$book_id);
						}

						// Assign the image to the page.
						if ( $new_image_id && $new_page_id ) {
							$new_assignment_id = $comic_image-> assignImageToPage($new_image_id,$new_page_id);
						}
						$i += 0.0001;
					}
					elseif ( $success !== false ) {
						$total_success = false;
						$alert_output .= $message-> alert_dialog('I couldn’t import images from '.$folder.'.');
					}

				}

			}
		}
	}

	reset_page_order($book_id,$db);
	if ( $i > 1 ) {
		$link->url('book.view.php');
		$link->tap('Check ’em out');
		$alert_output .= $message-> success_dialog('Hooray! Images imported. '.$link-> paint().'.');
	}








/*****
 * Display
 */

// Reset in case of form submission.
$folder_list = array();
$file_list = array();

// What’s in the import folder?
$import_top_list = $fileops-> get_dir_list($import_path);

if ( $import_top_list ) {
	foreach ( $import_top_list as $key => $val ) {
		if (is_dir($import_path.'/'.$val)) {
			$folder_list[$val] = $import_path.'/'.$val;
		}
		else {
			$file_list[$val] = $import_path.'/'.$val;
		}
	}
}
elseif ( !$_POST ) {
	$alert_output .= $message-> info_dialog('No folders found in /import on your web server. <a href="http://www.getgrawlix.com/docs/1/importing#ftp-importer">Learn more.</a>');
}



if ( $folder_list ) {

	$permissions_error_found = false;
	$total_count = 0;

	foreach ( $folder_list as $key => $val ) {
		$count = $fileops-> get_dir_list($val);
		$count = count($count);
		$total_count += $count;

		$permissions = fileperms($val);
		if ( $permissions && $permissions != '16895' ) {
			$permissions_error = '<strong>Access error</strong>';
			$permissions_error_found = true;
		}
		else {
			$permissions_error = 'Looks good';
		}

		$list_items[] = array(
			'&nbsp;',
			$key,
			$count,
			$permissions_error
		);
	}

	if ( $total_count && $total_count > 0 ) {
		if ( $permissions_error_found === false ) {
			$submit_output .= '<input type="submit" class="btn primary new" name="submit" value="Import to new '.$marker_type_list[1]['title'].'s"/>'."\n";
		}
		else {
			$submit_output .= '<input type="submit" class="btn primary new" name="submit" value="Try to import anyway"/>'."\n";
		}
	}
	elseif ( !$_POST ) {
		$alert_output .= $message-> info_dialog('I found folders, but no image files, in /import.');
	}
}


if ( $permissions_error_found === true ) {
	$alert_output .= $message-> alert_dialog('I may not be able to work with these files. Try temporarily setting them to 777 in FTP.');
}





/*****
 * Display
 */

if ( $list && $list_items && count($list_items) > 0 ) {
	$heading_list = array ('&nbsp;','Title','Images','Warnings');

	$list-> headings($heading_list);
	$list-> draggable(false);
	$list-> row_class('chapter');

	$list->headings($heading_list);
	$list->content($list_items);
	$folder_output  = $list->format_headings();
	$folder_output .= $list->format_content();

}


// Group

if ( $folder_output ) {
	$link-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/ftp');
	$link-> tap('via FTP');
	
	$link1-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/importing');
	$link1-> tap('Learn more.');
	
	$view->group_h2('Folders');
	$view->group_instruction('This tool lets you copy images from the “/import” folder ('.$link-> external_link().') into '.$marker_type_list[1]['title'].'s. '.$link1->external_link());
	$view->group_contents( $folder_output.$submit_output );
	$content_output .= $view->format_group()."\n";
}




$view->page_title('Create pages from FTP');
$view->tooltype('Chapter');
$view->headline('Create pages from FTP');

$link->url('./book.page-create.php');
$link->tap('Create one comic page');
$link->reveal(false);
$action_output = $link->button_secondary('new');

$view->action($action_output);

$output  = $view->open_view();
$output .= $view->view_header();
print($output);
?>

<form accept-charset="UTF-8" action="book.import.php" method="post">
<?=$alert_output ?>
<?=$content_output ?>
</form>
<?php

$output = $view->close_view();
print($output);
?>