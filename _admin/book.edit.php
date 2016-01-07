<?php

/*****
 * Setup
 */

require_once('panl.init.php');

$view = new GrlxView;
$modal = new GrlxForm_Modal;
$message = new GrlxAlert;
$link = new GrlxLinkStyle;
$list = new GrlxList;

$var_list = array(
	'book_id','new_title','publish_frequency','start_month','start_day','start_year'
);
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}

if ( $book_id ) {
	$book = new GrlxComicBook($book_id);
}
else {
	$book = new GrlxComicBook();
	$book_id = $book-> bookID;
}

$month_list = array (
	'01'=>'January',
	'02'=>'February',
	'03'=>'March',
	'04'=>'April',
	'05'=>'May',
	'06'=>'June',
	'07'=>'July',
	'08'=>'August',
	'09'=>'September',
	'10'=>'October',
	'11'=>'November',
	'12'=>'December'
);
$start_month_output = build_select_simple('start_month',$month_list, $date_start[1]);
for($i=1;$i<32;$i++){
	$i < 10 ? $j = '0'.$i : $j = $i;
	$day_list[$j] = $i;
}
$start_day_output = build_select_simple('start_day',$day_list, $date_start[2]);

for ( $y = date('Y')+1; $y >= 1980; $y--){
	$year_list[$y] = $y;
}
$start_year_output = build_select_simple('start_year',$year_list, $date_start[0]);

$frequency_list = array (
	'mwf' => 'Mon-Wed-Fri',
	'weekdays' => 'Weekdays',
	'saturdays' => 'Saturdays',
	'sundays' => 'Sundays',
	'mondays' => 'Mondays',
	'tuesdays' => 'Tuesdays',
	'wednesdays' => 'Wednesdays',
	'thursdays' => 'Thursdays',
	'fridays' => 'Fridays'
);


/*****
 * Updates
 */

if ( $publish_frequency && $start_month && $start_day && $start_year && $book_id ) {

	$date_start = $start_year.'-'.$start_month.'-'.$start_day;
	$data = array(
		'publish_frequency' => $publish_frequency,
		'date_start' => $date_start,
		'date_modified' => $db -> NOW()
	);
	$db -> where('id', $book_id);
	$db -> update('book', $data);
	$success = $db -> count;

	set_book_dates($book_id,$publish_frequency,$db);
}

if ( $book && $_POST ) {

	$data = array(
		'title' => $new_title,
		'publish_frequency' => $publish_frequency,
		'date_modified' => $db -> NOW()
	);
	$book-> saveInfo($data);
	$link-> url('book.view.php?book_id='.$book_id);
	$link-> tap('Peruse this book’s pages');
	$alert_output .= $message->success_dialog('Book info saved. '.$link-> paint().'.');
}


/*****
 * Display
 */

if ( !is_writable('../'.DIR_COMICS_IMG) ) {
	$alert_output .= $message->alert_dialog('The comics images folder is not writable. Please set '.DIR_COMICS_IMG.' to 0777 so I can upload and manage images.');
}

if ( $book_id ) {
	$book = new GrlxComicBook($book_id);
}
else {
	$book = new GrlxComicBook();
	$book_id = $book-> bookID;
}

if ( $frequency_list ) {
	$publish_frequency_output .= '<select name="publish_frequency" style="width:8rem">'."\n";
	foreach ( $frequency_list as $key => $val ) {
		if ( $key == $book->info['publish_frequency']) {
			$publish_frequency_output .= '<option selected="selected" value="'.$key.'">- '.$val.'</option>'."\n";
		}
		else {
			$publish_frequency_output .= '<option value="'.$key.'">'.$val.'</option>'."\n";
		}
	}
	$publish_frequency_output .= '</select>'."\n";
}

$marker_output = '<p><a href="marker-type.list.php">Edit marker types</a> (chapter, scene, etc)</p>';

$new_title_output = '<input type="text" name="new_title" value="'.$book->info['title'].'" size="16" style="width:16rem"/>'."\n";


$view->page_title("Book: $book_info[title]");
$view->tooltype('chap');
$view->headline('Book <span>'.$book->info['title'].'</span>');

// Group
$view->group_h2('Title');
$view->group_instruction('Change the name of your book.');
$view->group_contents($new_title_output);
$content_output .= $view->format_group().'<hr />';

// Group
$view->group_h2('Frequency');
$view->group_instruction('How often you publish new pages.');
$view->group_contents($publish_frequency_output);
$content_output .= $view->format_group().'<hr />';

// Group
$link-> title('Learn more about markers');
$link-> url('http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/markers');
$link-> tap('Markers');

$view->group_h2('Markers');
$view->group_instruction($link-> external_link().' are sections of a book, like chapters, scenes or supplemental material.');
$view->group_contents($marker_output);
$content_output .= $view->format_group();


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
//$output .= $modal->modal_container();
$output .= '<form accept-charset="UTF-8" method="post" action="book.edit.php">'."\n";
$output .= $content_output;
$output .= '<input type="hidden" name="book_id" value="'.$book_id.'">'."\n";
$output .= '<button class="btn primary save right" name="submit" type="submit" value="save"><i></i>Save</button>'."\n";
$output .= '</form>'."\n";
print($output);

print( $view->close_view() );
