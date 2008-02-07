<?php
ini_set('display_errors',1);
require_once("../../../wp-config.php");
require_once("../../../wp-includes/wp-db.php");
require_once("../../../wp-includes/pluggable.php");
global $flickr_manager;

if(!current_user_can('edit_plugins')) {
	die('Oops, sorry, you are not authorized to fiddle with plugins!');
}

if(isset($_FILES['uploadPhoto'])) {
	$token = $wpdb->get_var("SELECT value FROM $flickr_manager->db_table WHERE name='token'");

	/* Perform file upload */
	$file = $_FILES['uploadPhoto'];
	if($file['error'] == 0) {
		
		$params = array('auth_token' => $token, 'photo' => '@'.$file['tmp_name']);
		if(isset($_POST['photoTitle']) && !empty($_POST['photoTitle'])) $params = array_merge($params,array('title' => $_POST['photoTitle']));
		if(isset($_POST['photoTags']) && !empty($_POST['photoTags'])) $params = array_merge($params,array('tags' => $_POST['photoTags']));
		if(isset($_POST['photoDesc']) && !empty($_POST['photoDesc'])) $params = array_merge($params,array('description' => $_POST['photoDesc']));
		$rsp = $flickr_manager->upload($params);
		
		if($rsp !== false) {
		
			$xml_parser = xml_parser_create();
			xml_parse_into_struct($xml_parser, $rsp, $vals, $index);
			xml_parser_free($xml_parser);
			
			$pindex = $index['PHOTOID'][0];
			$pid = $vals[$pindex]['value'];
		}
	}
}
?>
<html>

<head>
	<link rel='stylesheet' href='<?php echo get_option('siteurl'); ?>/wp-admin/wp-admin.css' type='text/css' />
	<link rel="stylesheet" href="<?php echo $flickr_manager->getAbsoluteUrl(); ?>/css/admin_style.css" type="text/css" />
</head>

<body style="background-color: #f4f4f4;">
	<div id="uploadContainer">
		<form id="file_upload_form" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="padding: 0px 20px;">
			<h3>Upload Photo</h3>
			
			<table>
				<tbody>
					<tr>
						<td><label for="uploadPhoto">Upload Photo:</label></td>
						<td><input type="file" name="uploadPhoto" id="uploadPhoto" /></td>
					</tr>
					<tr>
						<td><label for="photoTitle">Title:</label></td>
						<td><input type="text" name="photoTitle" id="flickrTitle" /></td>
					</tr>
					<tr>
						<td><label for="photoTags">Tags:</label></td>
						<td><input type="text" name="photoTags" id="flickrTags" /> <sup>*Space separated list</sup></td>
					</tr>
					<tr>
						<td><label for="photoDesc">Description:</label></td>
						<td><textarea name="photoDesc" id="flickrDesc" rows="4"></textarea></td>
					</tr>
				</tbody>
			</table>
			<p class="submit" style="text-align: right;">
				<input type="submit" name="Submit" value="<?php _e('Upload &raquo;') ?>" />
				<input type="hidden" name="faction" id="flickr-action" value="<?php echo $_REQUEST['faction']; ?>" />
			</p>
			
		</form>
	</div>
</body>

</html>
