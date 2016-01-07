<?php

/*****
 * Setup
 */

require_once('panl.init.php');
require_once('lib/htmLawed.php');

$link1 = new GrlxLinkStyle;

$view = new GrlxView;
$message = new GrlxAlert;
$marker = new GrlxMarker;
$fileops = new GrlxFileOps;
$sl = new GrlxSelectList;

$view-> yah = 3;

$var_list = array('page_id','new_page_name','remove_id','blog_headline');
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}

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





// register_variable strips needed whitespace from text blocks
$transcript = $_POST['transcript'];
$transcript ? $transcript : $transcript = $_GET['transcript'];
$transcript ? $transcript : $transcript = $_SESSION['transcript'];
$blog_post = $_POST['blog_post'];
$blog_post ? $blog_post : $blog_post = $_GET['blog_post'];
$blog_post ? $blog_post : $blog_post = $_SESSION['blog_post'];

$pub_year = $_POST['pub_year'];
$pub_month = $_POST['pub_month'];
$pub_day = $_POST['pub_day'];

if ( !$page_id ) {
	header('location:book.view.php');
	die();
}




/////// ! Updates

$which = 'file_change';
if ( $_FILES[$which] && count($_FILES[$which]['name']) > 0 ) {
	foreach ( $_FILES['file_change']['name'] as $key => $val ) {

		// If there’s a folder to hold the previous image, use it for this new image.
		if (
			$_POST['original_path'][$key]
			&& strlen($_POST['original_path'][$key]) > 0 
			&& is_dir('../'.$_POST['original_path'][$key])
		) {
			$path = $_POST['original_path'][$key];
		}
		// If not, make one.
		else {
			$serial = date('YmdHis').substr(microtime(),2,6);
			$path = '/'.DIR_COMICS_IMG.$serial;
			mkdir('..'.$path); 
		}

		// Error handling
		$path = str_replace('//', '/', $path);

		// Move the file to its new home.
		$success1 = move_uploaded_file($_FILES[$which]['tmp_name'][$key], '../'.$path.'/'.$val);

		if ( !$success1 ) {
			if ( !is_writable('../'.$path)) {
				$alert_output .= $message->alert_dialog('Unable to upload image. Looks like a folder permissions problem.');
			}
			else {
				// See http://php.net/manual/en/features.file-upload.errors.php
				switch ( $_FILES[$which]['error'][$key] ) {
					case 1:
						$alert_output .= $message->alert_dialog('I couldn’t upload the image. It exceeded the server’s '.(ini_get( 'upload_max_filesize' )).'B file size limit.');
						break;
					case 2:
						$alert_output .= $message->alert_dialog('I couldn’t upload the image. It exceeded the server’s '.(ini_get( 'upload_max_filesize' )).'B file size limit.');
						break;
					case 3:
						$alert_output .= $message->alert_dialog('I couldn’t receive the image. There was nothing to receive.');
						break;
					case 6:
						$alert_output .= $message->alert_dialog('I couldn’t receive the image. There was no “temp” folder on the server — contact your host.');
						break;
					case 8:
						$alert_output .= $message->alert_dialog('I couldn’t upload the image. It doesn’t look like a PNG, GIF, JPG, JPEG or SVG.');
						break;
				}
			}
		}

		// Update the DB image reference to use the new file.
		if ( $success1 ) {
			if ($key && $key > 0 ) {
				$data = array(
					'url' => $path.'/'.$val,
					'date_modified' => $db->now()
				);
	
				$db->where('id',$key);
				$success2 = $db->update('image_reference', $data);
			}
			// Or add an image reference to the database.
			else {
				$data = array(
					'url' => $path.'/'.$val,
					'date_modified' => $db->now()
				);
				$success3 = $db->insert('image_reference', $data); // I used success3 to differentiate between DB events.

				$data = array(
					'rel_id' => $page_id,
					'rel_type' => 'page',
					'image_reference_id' => $success3,
					'date_modified' => $db->now()
				);

				$success3 = $db->insert('image_match', $data);
			}
		}
	}
}


if ( $page_id && $_POST ) {

	$data = array(
		'title' => $new_page_name,
		'date_modified' => $db -> NOW()
	);
	$db -> where('id', $page_id);
	$success = $db -> update('book_page', $data);

	if ( $_POST['image_description'] ) {
		foreach ( $_POST['image_description'] as $key => $val ) {
			$data = array(
				'description' => $val,
				'date_modified' => $db -> NOW()
			);
			$db -> where('id', $key);
			$success = $db -> update('image_reference', $data);
		}
	}
	
	$db -> where('id', $page_id);
	$success = $db -> update('book_page', $data);

	$link1-> url('book.view.php');
	$link1-> tap('Return to the page list');
	$go_return_link = $link1-> paint();

	$db-> where('id', $page_id);
	$current_order = $db-> getOne('book_page', 'sort_order');

	$db-> where('sort_order', $current_order['sort_order'],'>');
	$db-> orderBy('sort_order','ASC');
	$next_id = $db-> getOne('book_page', 'id');

	$link1-> url('book.page-edit.php?page_id='.$next_id['id']);
	$link1-> tap('Go to the next page');
	$go_next_link = $link1-> paint();

	if ( $success == 1 ) {
		$success_message = <<<EOL
Changes to <b>$new_page_name</b> were saved.
<ul>
	<li>Make more changes below</li>
	<li>$go_return_link</li>
	<li>$go_next_link</li>
</ul>

EOL;

		if ( $success || $success1 ) {
			$alert_output .= $message->success_dialog($success_message);
		}
	}
	else {
		$alert_output .= $message->alert_dialog('Unable to save <b>'.$new_page_name.'</b> change.');
	}
}

if ( $page_id && $_POST ) {

	$blog_post = htmLawed($blog_post);
	$transcript = htmLawed($transcript);
	$data = array(
		'blog_title' => $blog_headline,
		'blog_post' => $blog_post,
		'transcript' => $transcript,
		'date_publish' => $pub_year.'-'.$pub_month.'-'.$pub_day,
		'date_modified' => $db -> NOW()
	);
	$db -> where('id', $page_id);
	$db -> update('book_page', $data);
}

if ( $page_id && $remove_id ) {
	$db -> where('id', $remove_id);
	$db -> delete('image_match');
}





/////// ! Display logic

if ( $page_id ) {
	$page = new GrlxComicPage($page_id);
}

if ( $page-> pageInfo['book_id'] ) {
	$book = new GrlxComicBook($page-> pageInfo['book_id']);
	$book-> getPages();
}

// Let artists jump to this page in their website.
/*
if ( $book_info['url'] ) {

	$link1-> url($book_info['url'].'/'.$page_info['sort_order']);
	$link1-> tap('View this page live');
	$link1-> title('See this page as a reader would.');
	$action_output = $link1->text_link('view');

}
*/

$sl-> setName('pub_year');
$sl-> setCurrent(substr($page-> pageInfo['date_publish'],0,4));
$sl-> setList($year_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:4rem');
$year_select_output = $sl-> buildSelect();

$sl-> setName('pub_month');
$sl-> setCurrent(substr($page-> pageInfo['date_publish'],5,2));
$sl-> setList($month_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:8rem');
$month_select_output = $sl-> buildSelect();

$sl-> setName('pub_day');
$sl-> setCurrent(substr($page-> pageInfo['date_publish'],8,2));
$sl-> setList($day_list);
$sl-> setValueID('id');
$sl-> setValueTitle('title');
$sl-> setStyle('width:3rem');
$day_select_output = $sl-> buildSelect();

$meta_output .= '		<label for="new_page_name">Page title</label>'."\n";
$meta_output .= '		<input type="text" name="new_page_name" id="new_page_name" value="'.$page-> pageInfo['title'].'" style="max-width:40rem"/>';

$meta_output .= '		<label>Publication date</label>'."\n";
$meta_output .= $day_select_output;
$meta_output .= $month_select_output;
$meta_output .= $year_select_output;

// I think it’s time we blow this thing. 
// Get everybody and their stuff together.
// OK, three two one let’s jam.
if ( $book-> pageList ) {

	$last = end($book-> pageList);
	$last_id = $last['id'];

	$first = reset($book-> pageList);
	$first_id = $first['id'];

	$next = $page-> pageInfo['sort_order'] + 1;
	$next = $book-> pageList[$next.'.0000'];
	$next_id = $next['id'];

	$back = $page-> pageInfo['sort_order'] - 1;
	$back = $book-> pageList[$back.'.0000'];
	$back_id = $back['id'];

	if ( $first_id == $page_id ) {
		$next_page_id = $book->pageList[2]['id'];
	}

	if ( $first_id == $page_id ) {
		unset($first_page_id);
	}

	if ( $last_id == $page_id ) {
		unset($last_page_id);
	}

}


if ( $next_id ) {
	$link1-> url('book.page-edit.php?page_id='.$next_id);
	$link1-> tap('next &gt;');
	$next_link = $link1-> paint();
}
else {
	$next_link = 'next &gt;';
}
if ( $back_id ) {
	$link1-> url('book.page-edit.php?page_id='.$back_id);
	$link1-> tap('&lt; back');
	$back_link = $link1-> paint();
}
else {
	$back_link = '&lt; back';
}

if ( $first_id ) {
	$link1-> url('book.page-edit.php?page_id='.$first_id);
	$link1-> tap('&lt;&lt; first');
	$first_link = $link1-> paint();
}
else {
	$first_link = '&lt;&lt; first';
}
if ( $last_id ) {
	$link1-> url('book.page-edit.php?page_id='.$last_id);
	$link1-> tap('last &gt;&gt;');
	$last_link = $link1-> paint();
}
else {
	$last_link = 'last &gt;&gt;';
}



// Display each image. Let the user trash ’em as needed.

if ( $page-> imageList ) {


	if ( count($page-> imageList) > 1 ) {
		$row_divider .= '<hr/>';
	}

	foreach ( $page-> imageList as $key => $val ) {

		$ref_id = $val['image_reference_id'];
		if ( count ( $page-> imageList ) > 1 ) {
			$link1-> url('book.page-edit.php?page_id='.$page_id.'&amp;remove_id='.$key);
			$link1-> tap(' Delete this image');
			$link1-> title('Permanently delete the graphic from this comic page. There is no undo. Do not pass go. Do not collect 200 cubits.');
			$link1-> anchor_class('warning');
			$link1-> icon('delete');
			$delete_me = $link1-> paint();
		}

		if ( is_file('../'.$milieu_list['directory']['value'].$val['url'])) {
			$image_dimensions = getimagesize('../'.$milieu_list['directory']['value'].$val['url']);
			$image_bytes = filesize('../'.$milieu_list['directory']['value'].$val['url']);
			$weight = figure_pixel_weight($image_dimensions[0],$image_dimensions[1],$image_bytes);

		}
		else {
			$weight = 0;
		}
		$weight = round($weight,3);

		!$milieu_list['directory']['value'] || $milieu_list['directory']['value'] == '' ? $url_prefix = '../' : $url_prefix = '../';

		$this_image = '<img src="'.$url_prefix.$milieu_list['directory']['value'].$val['url'].'" alt="'.$val['description'].'"/>'."\n";

    $link1-> title = 'Learn more about pixel weight';
    $link1-> url = 'http://getgrawlix.com/docs/'.DOCS_VERSION.'/image-optimization';
    $link1-> tap = 'bytes/pixel';

		$this_description = '<p><label for="image_description['.$val['image_reference_id'].']">Description (alt text)</label>'."\n";

		$this_description .= '<input type="text" name="image_description['.$val['image_reference_id'].']" id="image_description['.$val['image_reference_id'].']" value="'.$val['description'].'" style="max-width:20rem"/><br/>'."\n";

		$this_weight .= 'Weight: '.$weight.' '.$link1-> paint().'</p>'."\n";

		$temp_path = explode('/',$val['url']);
		array_pop($temp_path);
		$temp_path = implode('/',$temp_path);

		$path_output .= '<input type="hidden" name="original_path['.$val['image_reference_id'].']" value="'.$temp_path.'" />'."\n";

		$main_image_output .= <<<EOL
$row_divider
<div class="row">
	<div class="medium-3 columns">
			<input type="file" name="file_change[$ref_id]" value=""/><br/>
			<button class="btn secondary upload" id="submit" name="edit-submit" type="submit" value="save"/><i></i>Replace</button><br/>
		<p>$this_description</p>
		<p>$this_weight</p>
		<p>$delete_me</p>
	</div>
	<div class="medium-9 columns">
		$this_image
	</div>
</div>


EOL;





	}
}

$head_output = <<<EOL


		<p style="text-align:right">
			$first_link | $back_link |
			$next_link | $last_link
		</p>
$main_image_output

EOL;



// This exposition says “here are the images in this page.”
if ( count($page-> pageInfo['images']) == 1 ) {
	$image_count_output = '1 image in this page';
}
else {
	$image_count_output = count($page-> pageInfo['images']).' images in this page';
}


if ( $page-> pageInfo && $page-> pageInfo['title'] ) {
	$heading_output = $page-> pageInfo['title'];
}
else {
	$heading_output = 'Unknown page';
}


if ( $page-> pageInfo['blog_title'] ) {
	$headline_output = $page-> pageInfo['blog_title'];
}

$blog_output = <<<EOL
<label for="blog_headline">Headline</label>
<input type="text" name="blog_headline" id="blog_headline" value="$headline_output"/>
<label for="blog_post">Post</label>
<textarea name="blog_post" id="blog_post" rows="32">{$page-> pageInfo[blog_post]}</textarea>

EOL;

$transcript_output = <<<EOL
<label for="transcript">Transcript</label>
<textarea name="transcript" id="transcript" rows="32">{$page-> pageInfo[transcript]}</textarea>

EOL;








$content_output = '<hr />';

$link1-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/seo');
$link1-> tap('Metadata');

$view->group_css('page');
$view->group_h2('Meta');
$view->group_contents($meta_output);
$view->group_instruction($link1->external_link().' is information that describes this comic page.');
$content_output .= $view->format_group().'<hr />';

$link1-> url('http://daringfireball.net/projects/markdown');
$link1-> tap('Markdown');

$view->group_css('page');
$view->group_h2('Blog');
$view->group_instruction('Write your thoughts for the day on this comic page. The post accepts HTML and '.$link1-> external_link().'.');
$view->group_contents($blog_output);
$content_output .= $view->format_group().'<hr />';

$link1-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/seo');
$link1-> tap('SEO');

$view->group_css('page');
$view->group_h2('Transcript');
$view->group_instruction('Transcript is a record of the the dialog, events and scenes in a comic page. In short, a script. Use this to improve accessibility and '.$link1-> external_link().'.');
$view->group_contents($transcript_output);
$content_output .= $view->format_group();




/////// ! Display

$view->page_title("Comic page: $heading_output");
$view->tooltype('chap');
$view->headline("Comic page <span>$heading_output</span>");
$view->action($action_output);

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
//$output .= $modal->modal_container();
//$output .= $content_output;
print($output);

?>


<form accept-charset="UTF-8" action="book.page-edit.php" method="post" enctype="multipart/form-data">
	<input type="hidden" name="page_id" value="<?=$page_id?>"/>

<?=$head_output ?>

<?=$content_output ?>
<?=$path_output ?>

<hr/><button class="btn primary save right" name="submit" type="submit" value="save"/><i></i>Save</button>

</form>

<?php

$view->add_jquery_ui();
$view->add_inline_script($js_call);
print($view->close_view());
?>