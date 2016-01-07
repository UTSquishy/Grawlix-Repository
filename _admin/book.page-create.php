<?php

// ! Setup


require_once('panl.init.php');
require_once('lib/htmLawed.php');

$link = new GrlxLinkStyle;
$view = new GrlxView;
$message = new GrlxAlert;
$fileops = new GrlxFileOps;
$comic_image = new GrlxComicImage;
$sl = new GrlxSelectList;

$view-> yah = 1;

$var_list = array('page_id','new_page_name','blog_headline','book_id','beginning_end','into_marker_id');
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}




if ( !$book_id ) {
	$book = new GrlxComicBook;
	$book_id = $book-> bookID;
}
else {
	$book = new GrlxComicBook($book_id);
}

$book-> getMarkers();

// register_variable strips needed whitespace from text blocks
$transcript = $_POST['transcript'];
$transcript ? $transcript : $transcript = $_GET['transcript'];
$transcript ? $transcript : $transcript = $_SESSION['transcript'];
$blog_post = $_POST['blog_post'];
$blog_post ? $blog_post : $blog_post = $_GET['blog_post'];
$blog_post ? $blog_post : $blog_post = $_SESSION['blog_post'];




// ! Updates


if ( $_POST && $_FILES['file_change']['name']['0'] != '' ) {

	// ! Add to a marker, if necessary.

	if ( $into_marker_id && is_numeric($into_marker_id) ) {
		$marker = new GrlxMarker($into_marker_id);
	}
	if ( $marker ) {
		if ( $marker-> pageList ) {
			$start_page = reset($marker-> pageList);
			$start_tone_id = $start_page['tone_id'];
		}
		$start_tone_id ? $start_tone_id : $start_tone_id = 1; // Better check to make sure that’s valid.
		if ( $beginning_end == 'beginning' ) {
			$sort_order = $marker-> startPage + 0.001;
		}
		else {
			$sort_order = $marker-> endPage + 0.001;
		}
	}
	else {
		$sort_order = $book-> lastPage + 0.001;
	}

	if ( $into_marker_id && !is_numeric($into_marker_id) ) {
		if ( $beginning_end == 'beginning' ) {
			$sort_order = 0.001;
		}
		else {
			$result = $db-> get ('book_page',1,'MAX(sort_order) AS endpage');
			if ( $result ) {
				$sort_order = $result[0]['endpage'];
			}
			else {
				$sort_order = 1.001;
			}
		}
	}

	// ! Add the page to MySQL.
	$new_page_name ? $new_page_name : $new_page_name = 'Untitled';
	$blog_post = htmLawed($blog_post);
	$transcript = htmLawed($transcript);
	$data = array(
		'sort_order' => $sort_order,
		'title' => $new_page_name,
		'book_id' => $book_id,
		'tone_id' => $start_tone_id,
		'blog_title' => $blog_headline,
		'blog_post' => $blog_post,
		'transcript' => $transcript,
		'date_modified' => $db -> NOW(),
		'date_publish' => $db -> NOW()
	);
	$new_page_id = $db -> insert('book_page', $data);
	if ( $new_page_id ) {
		reset_page_order($book_id,$db);
	}
}
elseif ( $_POST ) {
	$alert_output .= $message->alert_dialog('Huh, I didn’t find any images. Did you select some pics from your computer?');
}

if ( $_FILES['file_change'] && $new_page_id ) {

	$fileops-> up_set_destination_folder('../'.DIR_COMICS_IMG);
	$success = $fileops-> up_process('file_change');

	if ( $success && $new_page_id ) {
		foreach ( $success as $filename ) {

			// Figure the real file name to make an alt attribute.
			$alt = explode('/', $filename); // Break into parts
			$alt = end($alt); // Get the last part (should be the file name)
			$alt = explode('.',$alt); // Break into parts
			array_pop($alt); // Remove the last part (should be the extension)
			$alt = implode('.', $alt); // Put it back together

			// Create the image DB record.
			$new_image_id = $comic_image-> createImageRecord ( '/'.DIR_COMICS_IMG.$filename,$alt );

			// Assign the image to the page.
			if ( $new_image_id && $new_page_id ) {
				$new_assignment_id = $comic_image-> assignImageToPage($new_image_id,$new_page_id);
			}
		}

		header('location:book.page-edit.php?page_id='.$new_page_id);
		die();
	}
}




// ! Display logic


// Build calendar options (month list, day list, year list)
for ( $i=1; $i<32; $i++ ) {
	$i < 10 ? $i = '0'.$i : null;
	$day_list[$i] = array(
		'title' => $i,
		'id' => $i
	);
}

for ( $i=1; $i<13; $i++ ) {
	$i < 10 ? $i = '0'.$i : null;
	$month_list[$i] = array(
		'title' => date("F", mktime(0, 0, 0, $i, 1, 2015)), 
		'id' => $i
	);
}

for ( $i=date('Y')-10; $i<date('Y')+2; $i++ ) {
	$year_list[$i] = array(
		'title' => $i,
		'id' => $i
	);
}

// Build select elements for each date part.

$sl-> setName('pub_year');
$sl-> setCurrent(date('Y'));
$sl-> setList($year_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:4rem');
$year_select_output = $sl-> buildSelect();

$sl-> setName('pub_month');
$sl-> setCurrent(date('m'));
$sl-> setList($month_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:8rem');
$month_select_output = $sl-> buildSelect();

$sl-> setName('pub_day');
$sl-> setCurrent(date('d'));
$sl-> setList($day_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:3rem');
$day_select_output = $sl-> buildSelect();


if ( !is_writable('../'.DIR_COMICS_IMG) ) {
	$alert_output .= $message-> alert_dialog('I can’t write to the '.DIR_COMICS_IMG.' directory. Looks like a permissions problem.');
}

$marker_type_list = $db-> get ('marker_type',null,'id,title');
$marker_type_list = rekey_array($marker_type_list,'id');


$choose_marker_output  = <<<EOL
<input type="radio" name="beginning_end" value="beginning" id="beginning"/>
<label for="beginning" style="display:inline">Beginning of</label>

EOL;
$choose_marker_output .= <<<EOL
<input type="radio" checked="checked" name="beginning_end" value="end" id="end_of"/>
<label for="end_of">End of</label>

EOL;

$choose_marker_output .= '<select name="into_marker_id" style="width:12rem">'."\n";
$choose_marker_output .= '	<option value="the_book">the book</option>'."\n";
if ( $book-> markerList && count($book-> markerList) > 0 ) {
	foreach ( $book-> markerList as $key => $val ) {

		$type = $marker_type_list[$val['marker_type_id']];
		$choose_marker_output .= '<option value="'.$val['id'].'">'.$type['title'].': '.$val['title'].'</option>'."\n";
	}
}
else {
	$choose_marker_output .= 'the book'."\n";
}
$choose_marker_output .= '</select>'."\n";


$meta_output .= '		<label for="new_page_name">Page title</label>'."\n";
$meta_output .= '		<input type="text" name="new_page_name" id="new_page_name" value="'.$new_page_name.'" style="max-width:20rem"/>';

$meta_output .= '<label>Publication date</label>'."\n";
$meta_output .= $day_select_output;
$meta_output .= $month_select_output;
$meta_output .= $year_select_output;



/*
if ( $book_page_list ) {
	foreach ( $book_page_list as $key => $val ) {
		if ( $back_page_id ) {
			$next_page_id = $val['id'];
			break;
		}
		if ( $val['id'] == $page_id ) {
			$back_page_id = $last_id;
		}
		$last_id = $val['id'];
	}
	$first_page_id = reset($book_page_list);
	$first_page_id = $first_page_id['id'];
	$last_page_id = end($book_page_list);
	$last_page_id = $last_page_id['id'];

	if ( $first_page_id == $page_id ) {
		$next_page_id = $book_page_list[1]['id'];
	}

	if ( $first_page_id == $page_id ) {
		unset($first_page_id);
	}

	if ( $last_page_id == $page_id ) {
		unset($last_page_id);
	}

}
*/







$blog_output = <<<EOL
<label for="blog_headline">Headline</label>
<input type="text" name="blog_headline" id="blog_headline" value="$headline_output"/>
<label for="blog_post">Post</label>
<textarea name="blog_post" id="blog_post" rows="7">$page_info[blog_post]</textarea>

EOL;

$transcript_output = <<<EOL
<label for="transcript">Transcript</label>
<textarea name="transcript" id="transcript" rows="7">$page_info[transcript]</textarea>
<button class="btn primary new" name="submit" type="submit" value="save"/><i></i>Create</button>

EOL;


$link->url('book.import.php');
$link->tap('Create multiple comic pages');
$link->reveal(false);
$action_output = $link->button_secondary('new');


//$action_output .= '<a href="#multiple">Upload multiple pics</a>'."\n";

$view->action($action_output);


$new_image = <<<EOL
<label for="file_change">Comic page image</label>
<input type="file" id="file_change" name="file_change[]" value="" multiple/>


EOL;


$content_output .= '<form accept-charset="UTF-8" action="book.page-create.php" method="post" enctype="multipart/form-data">'."\n";

$view->group_css('page');
$view->group_h2('Image');
$view->group_instruction('Upload the graphic(s) that readers will see on this page.');
$view->group_contents($new_image);
$content_output .= $view->format_group()."<hr/>\n";

$view->group_css('page');
$view->group_h3('Order');
$view->group_instruction('Choose where in your book the new page will go.');
$view->group_contents($choose_marker_output);
$content_output .= $view->format_group()."<hr/>\n";

$link-> title = 'Learn more about metadata';
$link-> url = 'http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/metadata';
$link-> tap = 'information that describes';
$link-> transpose = false;

$view->group_css('page');
$view->group_h3('Metadata');
$view->group_instruction('Enter information about this page. Learn more about '.$link-> external_link().' this comic page.');
$view->group_contents($meta_output);
$content_output .= $view->format_group()."<hr/>\n";

$view->group_css('page');
$view->group_h3('Blog');
$view->group_instruction('Your thoughts of the day.');
$view->group_contents($blog_output);
$content_output .= $view->format_group()."<hr/>\n";

$link-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/seo');
$link-> tap('SEO');

$view->group_css('page');
$view->group_h3('Transcript');
$view->group_instruction('Dialogue, scene descriptions, etc — great '.$link-> external_link().' stuff.');
$view->group_contents($transcript_output);
$content_output .= $view->format_group();

$content_output .= '<input type="hidden" name="book_id" value="'.$book_id.'"/>'."\n";
$content_output .= '</form>'."\n";

//$content_output .= '<hr/><h1 id="multiple">Multiple new pages</h1><br/>'."\n";

/*
$view->group_css('page');
$view->group_h2('Quick add');
$view->group_instruction('Just add page(s) to the end of your book. No frills, just pics.');
$view->group_contents($quick_upload_field);
$content_output .= $view->format_group()."\n";
*/







// ! Display


$view->page_title('Comic page creator');
$view->tooltype('page');
$view->headline('Create a new page');
$view->action($action_output);

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
//$output .= $modal->modal_container();
//$output .= $content_output;
print($output);

?>



<?=$images_output ?>

<?=$content_output ?>

<?php
$view->add_jquery_ui();
$view->add_inline_script($js_call);
print($view->close_view());
?>