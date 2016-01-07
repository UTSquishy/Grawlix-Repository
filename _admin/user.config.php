<?php

/* Artists use this script to control their user settings.
 */

/*****
 * Functions
 */

function fetch_all_users($db) {
	$cols = array('username', 'email', 'id');
	$users = $db -> get('user', null, $cols);
	foreach ( $users as $user ) {
		$user_list[$user['id']] = $user;
	}
	return $user_list;
}


/*****
 * Setup
 */

require_once('panl.init.php');
require_once('../_system/password.php');

$view = new GrlxView;
$form = new GrlxForm;

$var_list = array('username', 'password', 'email', 'submit');
if ( $var_list ) {
	foreach ( $var_list as $key => $val ) {
		$$val = register_variable($val);
	}
}

$view-> yah = 13;

/*****
 * Updates
 */

if ( $_POST['submit'] ) {
	foreach ( $_POST['username'] as $key => $val ) {
		$username_change = $val;
		$email_change = $email[$key];
		if ( $username_change != '' ) {
			$data = array(
				'username' => $username_change,
				'email' => $email_change,
				'date_modified' => $db -> now(),
			);
			$db -> where('id', $key);
			$message = new GrlxAlert;
			if ( $db -> update('user', $data) ) {
				$alert_output = $message->success_dialog('Changes were saved.');
			}
			else {
				$alert_output = $message->alert_dialog('Changes failed to save.');
			}
		}
	}

	foreach ( $_POST['password'] as $key => $val ) {
		if ( $val != '' ) {
			$val_hash = password_hash( $val, PASSWORD_BCRYPT );
			if ( password_verify($val, $val_hash) ) {
				$data = array(
					'password' => $val_hash,
					'date_modified' => $db -> now(),
				);
				$db -> where('serial', $_SESSION['admin']);
				$message = new GrlxAlert;
				if ( $db -> update('user', $data) ) {
					$alert_output = $message->success_dialog('New password saved.');
				}
				else {
					$alert_output = $message->alert_dialog('New password failed to save.');
				}
			}
		}
	}
}


/*****
 * Display logic
 */

$db-> where('serial',$_SESSION['admin']);
$user_list = $db -> get('user', null, 'username,email,id');

if ( $user_list ) {
	foreach ( $user_list as $key => $val ) {
		$form->input_text("username[$val[id]]");
		$form->label('Username');
		$form->required(true);
		$form->value($val['username']);
		$form->maxlength(16);
		$form->size(16);
		$details_output .= $form->paint();

		$form->input_email("email[$val[id]]");
		$form->size(16);
		$form->value($val['email']);
		$details_output .= $form->paint();
		$details_output .= $form->form_buttons();

		$password_output  = $form->new_password("password[$val[id]]");
		$password_output .= $form->form_buttons();
	}
}

$view->page_title('User info');
$view->tooltype('user');
$view->headline('User info');

$form->send_to($_SERVER['SCRIPT_NAME']);

$view->group_css('user');
$view->group_h2('Details');
$view->group_contents($details_output);
$content_output = $view->format_group().'<hr />';
$view->group_h2('Password');
$view->group_css('user');
$view->group_contents($password_output);
$content_output .= $view->format_group();


/*****
 * Display
 */

$output  = $view->open_view();
$output .= $view->view_header();
$output .= $alert_output;
$output .= $form->open_form();
$output .= $content_output;
$output .= $form->close_form();
$output .= $view->close_view();
print($output);
