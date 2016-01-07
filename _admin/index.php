<?php

/* ! Setup * * * * * * * */

require_once('panl.init.php');

$update = 'update.php';

if ( file_exists($update) ) {
	$message = new GrlxAlert;
	$alert_output = $message->info_dialog('Welcome to 1.0!<br /><br />You should <a href="'.$update.'?go=1">update your database</a> now.');
}

/*
$link_set[] = array(
	'url' => 'book.view.php',
	'tap' => 'Browse comic pages',
	'title' => ''
);
$link_set[] = array(
	'url' => 'sttc.page-list.php',
	'tap' => 'Browse static pages',
	'title' => ''
);
$link_set[] = array(
	'url' => 'book.page-create.php',
	'tap' => 'Create comic page(s)',
	'title' => ''
);
$link_set[] = array(
	'url' => 'sttc.xml-create.php',
	'tap' => 'Create static page(s)',
	'title' => ''
);



$link_set[] = array(
	'url' => 'site.config.php',
	'tap' => 'Change my artist name',
	'title' => ''
);

$link_set[] = array(
	'url' => 'site.config.php',
	'tap' => 'Change my copyright year',
	'title' => ''
);

$link_set[] = array(
	'url' => 'user.config.php',
	'tap' => 'Change my login/password',
	'title' => ''
);
*/


/* ! Build * * * * * * * */

$view = new GrlxView;
$fileops = new GrlxFileOps;
$link = new GrlxLink;
$fileops->db = $db;
$view-> yah = 15;

$view->page_title('Grawlix panel');
$view->tooltype('panl');
$view->headline('Grawlix panel');

$db-> where('date_publish >= NOW()');
$db-> orderBy('sort_order','ASC');
$id_info = $db-> getOne('book_page','id');

if ( $id_info ) {
	$comic_page = new GrlxComicPage($id_info['id']);
}
if ( $comic_page ) {
	$image = reset($comic_page-> imageList);
	$link-> url('book.page-edit.php?page_id='.$comic_page-> pageID);
	$link-> tap($comic_page-> pageInfo['title']);
	$next_output  = '<p>'.$link-> paint().'</p>'."\n";
	$link-> tap('<img src="'.$image['url'].'" alt="" />');
	$next_output .= '<p>'.$link-> paint().'</p>'."\n";
}
else {
	$next_output = '<p><a href="book.view.php">Nothing’s coming up.</a></p>'."\n";
}


/*
if ( $link_set ) {
	$link_set_output = '<ul>'."\n";
	foreach ( $link_set as $key => $val ) {
		$link-> url($val['url']);
		$link-> tap($val['tap']);
		$link-> title($val['title']);
		$link_set_output .= '<li>'.$link-> paint().'</li>'."\n";
	}
	$link_set_output .= '</ul>'."\n";
}
*/


$docs_link = 'http://www.getgrawlix.com/docs/'.DOCS_VERSION.'/';

$link_set_output = <<<EOL

<h3><a href="$docs_link">Read the documentation</a></h3>

<h3><a href="site.config.php">General settings</a></h3>
<p>Change:</p>
<ul>
  <li>Artist name</li>
  <li>Copyright year</li>
  <li>Date format</li>
  <li>Google Analytics ID</li>
  <li>Site description</li>
  <li>Time zone</li>
  <li>Username/password</li>
</ul>


EOL;


// Group
$view->group_h2('I want to …');
//$view->group_instruction('');
$view->group_contents($link_set_output);
$content_output .= $view->format_group().'<hr/>'."\n";

/*
$view->group_h2('Coming up');
$view->group_instruction('The next comic will be …');
$view->group_contents($next_output);
$content_output .= $view->format_group().'<hr/>'."\n";
*/


/* ! Display * * * * * * * */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
$output .= $content_output;
$output .= $view->close_view();
print($output);


