<?php

/* Artists use this script to browse their static site pages.
 */

/*****
 * Setup
 */

require_once('panl.init.php');

$view = new GrlxView;
$modal = new GrlxForm_Modal;
$link = new GrlxLinkStyle;
$static = new GrlxStaticPage;

// Number of <item>s per page to show.
$preview_limit = 5;

$view-> yah = 6;


/*****
 * Updates
 */

if ( $_POST['modal-submit'] && is_numeric($_POST['delete_id']) ) {
	$delete_id = $_POST['delete_id'];
	$result = $db
		-> where('id', $delete_id)
		-> delete('static_page');
	$result = $db
		-> where('rel_id', $delete_id)
		-> where('rel_type', 'static')
		-> where('edit_path', 1)
		-> delete('path');
}


/*****
 * Display logic
 */

// Grab all pages from the database.
$page_list = $static->getPageList();

if ( $page_list ) {
	foreach ( $page_list as $key=>$val ) {

		if ( $val['url'] == '/' ) {
			$home = $key;
		}

		$preview = ''; // reset
		$i = 0; // reset

		// Get info about each page — specifically, we want its XML content and layout.
		$this_info = fetch_static_page_info($key,$db);

		// Reality check: Does this look like XML?
		$xml = ''; // reset
		if ( $this_info['options'] && substr($this_info['options'],0,5) == '<?xml' ) {

			// Try to load it as XML.
			@$xml = simplexml_load_string($this_info['options']);
		}
		else {
			$page_list[$key]['preview'] = 'Freeform content';
		}

		// Loop through the XML’s <item> elements.
		if ( $xml && $xml->content->item ) {
			foreach ( $xml->content->item as $key2 => $val2 ) {

				// Build previews — a glimpse into what each page’s XML holds.
				// But to a point. No sense in showing 15 items for one page,
				// three for another.
				if ( $i < $preview_limit ) {
					// Headings make for great previews.
					if ( $val2->heading && trim($val2->heading) != '' ) {
						$page_list[$key]['preview'] .= (string)$val2->heading.'<br/>';
					}

					// No headings? OK, use images.
					elseif ( $val2->image ) {
						$page_list[$key]['preview'] .= '<img src="'.(string)$val2->image.'" alt="'.(string)$val2->image.'" style="max-width:42px"/> ';
					}

					// No images? What about freeform content?
					elseif ( $val2->freeform ) {
						$page_list[$key]['preview'] .= htmlentities(substr((string)$val2->freeform,0,20)).'…';
					}
				}
				// Indicate there are more items we’re just not showing here.
				elseif ( $i == $preview_limit ) {
					$page_list[$key]['preview'] .= '&hellip;';
				}
				$i++;
			}
		}
	}
}


if ( $page_list ) {
	$page_list_output = '<ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4">'."\n";
	foreach ( $page_list as $key => $val ) {
		if ( $val['edit_path'] == 1 ) {
			$title = urlencode($val['title']);
			$delete_link = new GrlxLinkStyle;
			$delete_link->url('sttc.page-delete.ajax.php');
			$delete_link->title('Delete this page.');
			$delete_link->reveal(true);
			$delete_link->query("id=$val[id]&amp;title=$title");
			$this_action = $delete_link->icon_link('delete');
		}
		else {
			$delete_link = new GrlxLinkStyle;
			$delete_link->i_only(true);
			$delete_link->id();
			$this_action = $delete_link->icon_link('locked');
		}

		$page_list_output .= <<<EOL
		<li id="page-$val[id]">
		<div class="page sttc">
			<a href="sttc.xml-edit.php?page_id=$val[id]">
				<h3>$val[title]</h3>
				<p>$val[preview]</p>
			</a>
			$this_action
			<a class="edit" href="sttc.xml-edit.php?page_id=$val[id]">
				<i class="edit"></i>
			</a>
		</div>
		</li>
EOL;
	}
	$page_list_output .= '</ul>'."\n";
}


$view->page_title('Static pages');
$view->tooltype('sttc');
$view->headline('Static pages');

$link->url('site.nav.php');
$link->tap('Edit order and URLs');
$action_output = $link->text_link('menu');

$link->url('sttc.xml-new.php');
$link->tap('New page');
$action_output .= $link->button_secondary('new');

$view->action($action_output);


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $modal->modal_container();
$output .= $alert_output;
print($output);
?>
	<section class="sttc">
<?php
if ( $page_list_output ) {
	print($page_list_output);
}
else {
	$message = new GrlxAlert;
	print( $message->info_dialog('Your site has no static pages. No FAQ, no About the Artist, nothing.') );
}
?>
	</section>
<?php
$js_call = <<<EOL
	$( "a.delete i" ).hover( // highlight item to be deleted
		function() {
			$( this ).parent().parent().addClass("red-alert");
		}, function() {
			$( this ).parent().parent().removeClass("red-alert");
		}
	);
	$( "i.edit" ).hover( // highlight the editable item
		function() {
			$( this ).parent().parent().addClass("editme");
		}, function() {
			$( this ).parent().parent().removeClass("editme");
		}
	);
EOL;

$view->add_inline_script($js_call);
$output = $view->close_view();
print($output);
?>
