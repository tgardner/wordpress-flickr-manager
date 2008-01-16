<?php
/*
Plugin Name: Flickr Manager
Plugin URI: http://tgardner.net/
Description: Handles uploading, modifying images on Flickr, and insertion into posts.
Version: 1.4.0
Author: Trent Gardner
Author URI: http://tgardner.net/

Copyright 2007  Trent Gardner  (email : trent.gardner@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


global $wpdb;
$flickr_table = $wpdb->prefix . "flickr";
$flickr_db_version = "1.0";
$flickr_directory = dirname(__FILE__);

add_action('activate_wordpress-flickr-manager/flickr-manager-plugin.php', 'flickr_install');

function flickr_install() {
	global $wpdb, $flickr_db_version, $flickr_table;
	
	if($wpdb->get_var("SHOW TABLES LIKE '$flickr_table'") != $flickr_table) {
		/* Create Table */
		
		$sql = "CREATE TABLE $flickr_table (
				uid mediumint(9) NOT NULL AUTO_INCREMENT,
				name VARCHAR(55) NOT NULL,
				value VARCHAR(55),
				UNIQUE KEY id (uid)
				);";
		
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	
		add_option("flickr_db_version", $flickr_db_version);
	}
}

// Hook for adding admin menus
add_action('admin_menu', 'flickr_add_pages');

function flickr_add_pages() {
	// Add a new submenu under Manage
	add_management_page('Flickr Management', 'Flickr', 5, __FILE__, 'flickr_manage_page');
	
	// Add a new submenu under Options
	add_options_page('Flickr Options', 'Flickr', 5, __FILE__, 'flickr_options_page');
}

function flickr_manage_page() {
	
	global $wpdb, $flickr_table;
	$token = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='token'");
	
	if($_REQUEST['action'] == '4') {
		/* Perform file upload */
		$file = $_FILES['uploadPhoto'];
		if($file['error'] == 0) {
			
			$params = array('auth_token' => $token, 'photo' => '@'.$file['tmp_name']);
			$rsp = flickr_upload($params);
			
			if($rsp !== false) {
			
				$xml_parser = xml_parser_create();
				xml_parse_into_struct($xml_parser, $rsp, $vals, $index);
				xml_parser_free($xml_parser);
				
				$pindex = $index['PHOTOID'][0];
				$pid = $vals[$pindex]['value'];
				
				if(!empty($pid)) {
					$_REQUEST['pid'] = $pid;
					$_REQUEST['action'] = 1;
				}
				
			}
			
		}
	}
	
	if($_REQUEST['action'] == '3') {
		/* Perform modify */
		$params = array('photo_id' => $_REQUEST['pid'], 
						'title' => $_REQUEST['ftitle'],
						'description' => $_REQUEST['description'],
						'auth_token' => $token);
		
		flickr_post('flickr.photos.setMeta', $params, true);
		
		$params = array('photo_id' => $_REQUEST['pid'], 
						'tags' => $_REQUEST['tags'],
						'auth_token' => $token);
		
		flickr_post('flickr.photos.setTags', $params, true);
		
		$is_public = ($_REQUEST['public'] == '1') ? 1 : 0;
		$is_friend = ($_REQUEST['friend'] == '1') ? 1 : 0;
		$is_family = ($_REQUEST['family'] == '1') ? 1 : 0;
		$params = array('photo_id' => $_REQUEST['pid'], 
						'is_public' => $is_public,
						'is_friend' => $is_friend,
						'is_family' => $is_family,
						'perm_comment' => '3',
						'perm_addmeta' => '0',
						'auth_token' => $token);
		
		flickr_post('flickr.photos.setPerms', $params, true);
		
		$_REQUEST['action'] = 1;
	}
	
	if($_REQUEST['action'] == '2') {
		/* Perform delete */
		$pid = $_REQUEST['pid'];
		
		$params = array('auth_token' => $token, 'photo_id' => $pid);
		
		flickr_post('flickr.photos.delete', $params, true);
		
		$_REQUEST['action'] = 0;
	}
?>

	<div class="wrap">
	
		<h2>Image Management</h2>
		
		<?php
			if(!empty($token)) {
				$params = array('auth_token' => $token);
				$auth_status = flickr_call('flickr.auth.checkToken',$params, true); 
				$auth_status = $auth_status['stat'];
				if($auth_status != 'ok') {
					echo '<h3>Error: Please authenticate through Options->Flickr</h3>';
					return;
				}
			} else {
				echo '<h3>Error: Please authenticate through Options->Flickr</h3>';
				return;
			}
		?>
		
		<form enctype="multipart/form-data" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="padding: 0px 20px;">
			<h3>Upload Photo</h3>
			
			<p class="submit" style="text-align: left;">
				<label>Upload Photo:
					<input type="file" name="uploadPhoto" id="uploadPhoto" />
				</label>
				<input type="submit" name="Submit" value="<?php _e('Upload &raquo;') ?>" />
				<input type="hidden" name="action" value="4" />
			</p>
		</form>
		
		<div style="padding: 0px 20px;">
			<!-- Begin Modification Section -->
			
			<?php
			switch($_REQUEST['action']) {
				default: 
					$page = (isset($_REQUEST['fpage'])) ? $_REQUEST['fpage'] : '1';
					$per_page = (isset($_REQUEST['fper_page'])) ? $_REQUEST['fper_page'] : '10';
					$nsid = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='nsid'");
					$params = array('user_id' => $nsid, 'per_page' => $per_page, 'page' => $page, 'auth_token' => $token);
					$photos = flickr_call('flickr.people.getPublicPhotos', $params, true);
					$pages = $photos['photos']['pages'];
			?>
			
			<h3>Manage Photos:</h3>
			<p><b>Add images to your posts with [img:&lt;flickr-id&gt;,&lt;size&gt;]</b></p>
			<!-- Default management section -->
			
			<div style="text-align: center;">
			<table style="margin-left: auto; margin-right: auto;" class="widefat">
				<thead>
					<tr>
						<th width="130px" style="text-align: center;">ID</th>
						<th width="100px" style="text-align: center;">Thumbnail</th>
						<th width="200px" style="text-align: center;">Title</th>
						<th width="170px" style="text-align: center;">Action</th>
					</tr>
				</thead>
				
				<tbody id="the-list">
				
				<?php 
				$count = 0;
				foreach ($photos['photos']['photo'] as $photo) : 
					$count++;
				?>
				
				<tr <?php if($count % 2 > 0) echo "class='alternate'"; ?>>
					<td align="center"><?php echo $photo['id']; ?></td>
					<td align="center"><img src="<?php echo flickr_photo_url($photo,"square"); ?>" alt="<?php echo $photo['title']; ?>" /></td>
					<td align="center"><?php echo $photo['title']; ?></td>
					<td align="center"><a href="http://www.flickr.com/photos/<?php echo "$nsid/{$photo['id']}/"; ?>" target="_blank">View</a> / 
					<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;action=1&amp;pid={$photo['id']}"; ?>">Modify</a> / 
					<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;action=2&amp;pid={$photo['id']}"; ?>">Delete</a>
					</td>
				</tr>
				
				<?php endforeach; ?>
				
				</tbody>
				
			</table>
			
			<?php if (intval($page) > 1) : ?>
				
				<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;fpage=".(intval($page) - 1)."&amp;fper_page=$per_page"; ?>">&laquo; Previous</a>
				
			<?php endif; ?>
			
			<?php for($i=1; $i<=$pages; $i++) : ?>
				
				<?php if($i != intval($page)) : ?>
				
				<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;fpage=$i&amp;fper_page=$per_page"; ?>"><?php echo $i; ?></a>
				
				<?php else : 
					echo "<b>$i</b>";
					
				endif; ?>
				
			<?php endfor; ?>
			
			<?php if (intval($page) < $pages) : ?>
			
				<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;fpage=".(intval($page) + 1)."&amp;fper_page=$per_page"; ?>">Next &raquo;</a>
				
			<?php endif; ?>
			
			</div>
			
			<?php 
					break;
				
				case 1: 
					$pid = $_REQUEST['pid'];
					$params = array('photo_id' => $pid, 'auth_token' => $token);
					$photo = flickr_call('flickr.photos.getInfo',$params, true);
			?>
				
			<h3>Modify Photo</h3>
			<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}"; ?>" >&laquo; Back</a><br /><br />
			
			<!-- Begin modification of inidividual photo -->
			
			<div align="center">
				<img src="<?php echo flickr_photo_url($photo['photo'],"medium"); ?>" alt="<?php echo $photo['photo']['title']['_content']; ?>" /><br />
			
			
			<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="width: 650px;">
				<table>
					<tr>
						<td width="130px"><label for="ftitle">Title:</label></td>
						<td><input type="text" name="ftitle" id="ftitle" value="<?php echo $photo['photo']['title']['_content']; ?>" style="width:300px;" /></td>
					</tr>
					<tr>
						<td>Permissions:</td>
						<td>
						<label><input name="public" type="checkbox" id="public" value="1" <?php if($photo['photo']['visibility']['ispublic'] == '1') echo 'checked="checked" '; ?>/> Public</label>
						<label><input name="friend" type="checkbox" id="friend" value="1" <?php if($photo['photo']['visibility']['isfriend'] == '1') echo 'checked="checked" '; ?>/> Friends</label>
						<label><input name="family" type="checkbox" id="family" value="1" <?php if($photo['photo']['visibility']['isfamily'] == '1') echo 'checked="checked" '; ?>/> Family</label>
						</td>
					</tr>
					<tr>
						<td><label for="tags">Tags:</label></td>
						<td><input type="text" name="tags" id="tags" value="<?php 
						foreach($photo['photo']['tags']['tag'] as $tag) {
							echo "{$tag['raw']} ";
						}
						?>" style="width:500px;" /></td>
					</tr>
					<tr>
						<td valign="top"><label for="description">Description:</label></td>
						<td><textarea name="description" id="description" style="width:500px; height:100px;"><?php echo $photo['photo']['description']['_content']; ?></textarea></td>
					</tr>
				</table>
				<input type="hidden" name="action" value="3" />
				<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
				<input type="submit" name="submit" value="Submit" />
				<input type="reset" name="reset" value="Reset" />
			</form>
			</div>
			
			<?php
					break;
			}
			?>
			
		</div>
		
	</div>
	
<?php
}

function flickr_options_page() {
	global $wpdb, $flickr_table;
	
	ini_set('display_errors',1);
	
	if(!empty($_REQUEST['action'])) {
		switch (intval($_REQUEST['action'])) {
			
			case 1: // Get Token
				$frob = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='frob'");
				if(!empty($frob)) {
					$params = array('frob' => $frob);
					$token = flickr_call('flickr.auth.getToken',$params, true);
					
					if($token['stat'] == 'ok') {
						$nsid = $token['auth']['user']['nsid'];
						$user = $token['auth']['user']['username'];
						$token = $token['auth']['token']['_content'];
						
						$exists = $wpdb->get_var("SELECT COUNT(value) FROM $flickr_table WHERE name='token'");

						if(empty($exists)) {
							$sql = "INSERT INTO $flickr_table (name, value) VALUES ('token', '$token')";
						} else {
							$sql = "UPDATE $flickr_table SET value='$token' WHERE name='token'";
						}
						$wpdb->query($sql);
						
						$exists = $wpdb->get_var("SELECT COUNT(value) FROM $flickr_table WHERE name='nsid'");
						
						if(empty($exists)) {
							$sql = "INSERT INTO $flickr_table (name, value) VALUES ('nsid', '$nsid')";
						} else {
							$sql = "UPDATE $flickr_table SET value='$nsid' WHERE name='nsid'";
						}
						$wpdb->query($sql);
						
						$exists = $wpdb->get_var("SELECT COUNT(value) FROM $flickr_table WHERE name='username'");
						
						if(empty($exists)) {
							$sql = "INSERT INTO $flickr_table (name, value) VALUES ('username', '$user')";
						} else {
							$sql = "UPDATE $flickr_table SET value='$user' WHERE name='username'";
						}
						$wpdb->query($sql);
					}
				}
				break;
			
			case 2: // Logout
				$sql = "DELETE FROM $flickr_table";
				$wpdb->query($sql);
				break;
				
			case 3:
				if(!isset($_REQUEST['fper_page']) || !is_numeric($_REQUEST['fper_page']) || intval($_REQUEST['fper_page']) <= 0) $_REQUEST['fper_page'] = 5;
				$per_page = $_REQUEST['fper_page'];
				$exists = $wpdb->get_var("SELECT COUNT(value) FROM $flickr_table WHERE name='per_page'");
						
				if(empty($exists)) {
					$sql = "INSERT INTO $flickr_table (name, value) VALUES ('per_page', '$per_page')";
				} else {
					$sql = "UPDATE $flickr_table SET value='$per_page' WHERE name='per_page'";
				}
				$wpdb->query($sql);
				break;
			
		}
	}
	
	$token = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='token'");
	if(!empty($token)) {
		$params = array('auth_token' => $token);
		$auth_status = flickr_call('flickr.auth.checkToken',$params, true); 
		$auth_status = $auth_status['stat'];
	}
	?>
	<div class="wrap">
		<h2>Flickr Options</h2>
	
		<?php if(empty($token) || $auth_status != 'ok') : ?>
		
		<!-- Begin authentication -->
		
		<?php
		$frob = flickr_call('flickr.auth.getFrob',array(),true);
		$frob = $frob['frob']['_content'];
		$exists = $wpdb->get_var("SELECT COUNT(value) FROM $flickr_table WHERE name='frob'");
		if(empty($exists)) {
			$sql = "INSERT INTO $flickr_table (name, value) VALUES ('frob', '$frob')";
		} else {
			$sql = "UPDATE $flickr_table SET value='$frob' WHERE name='frob'";
		}
		$wpdb->query($sql);
		?>
		
		<div align="center">
			<h3>Step 1:</h3>
			<form>
				<input type="button" value="Authenticate" onclick="window.open('<?php echo flickr_auth_url($frob, "delete"); ?>')" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
			</form>
			
			<h3>Step 2:</h3>
			<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<!-- action=1 - getToken -->
				<input type="hidden" name="action" value="1" />
				<input type="submit" name="Submit" value="<?php _e('Finish &raquo;') ?>" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
			</form>
			
		</div>
		
		<?php else : ?>
		
		<!-- Display options -->
		<div align="center">
			<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<!-- action=2 - Logout -->
				<input type="hidden" name="action" value="2" />
				<input type="submit" name="Submit" value="<?php _e('Logout &raquo;') ?>" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
			</form>
		</div>
		
			<?php
			$nsid = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='nsid'");
			$info = flickr_call('flickr.people.getInfo',array('user_id' => $nsid));
			
			if($info['stat'] == 'ok') :
			?>
		
		<h3>User Information</h3>
		
		<table width="100%" border="0">
			<tr>
				<td width="130px"><b>Username:</b></td>
				<td><?php echo $info['person']['username']['_content']; ?></td>
			</tr>
			<tr>
				<td><b>User ID:</b></td>
				<td><?php echo $info['person']['nsid']; ?></td>
			</tr>
			<tr>
				<td><b>Real Name:</b></td>
				<td><?php echo $info['person']['realname']['_content']; ?></td>
			</tr>
			<tr>
				<td><b>Photo URL:</b></td>
				<td><a href="<?php echo $info['person']['photosurl']['_content']; ?>"><?php echo $info['person']['photosurl']['_content']; ?></a></td>
			</tr>
			<tr>
				<td><b>Profile URL:</b></td>
				<td><a href="<?php echo $info['person']['profileurl']['_content']; ?>"><?php echo $info['person']['profileurl']['_content']; ?></a></td>
			</tr>
			<tr>
				<td><b># Photos:</b></td>
				<td><?php echo $info['person']['photos']['count']['_content']; ?></td>
			</tr>
		</table>
		
		<p>&nbsp;</p>
		
		<h3>Optional Settings</h3>
		
		<?php 
		if(!isset($_REQUEST['fper_page']) || !is_numeric($_REQUEST['fper_page']) || intval($_REQUEST['fper_page']) <= 0) $_REQUEST['fper_page'] = 5; 
		$exists = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='per_page'");
		if(!empty($exists)) $_REQUEST['fper_page'] = $exists;
		?>
		
		<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<!-- action=3 - Update Options -->
			<input type="hidden" name="action" value="3" />
			<label>Images per page: <input type="text" name="fper_page" value="<?php echo $_REQUEST['fper_page']; ?>" style="padding: 3px; width: 50px;" /></label>
			<p class="submit">
				
				<input type="submit" name="Submit" value="<?php _e('Submit') ?> &raquo;" style="font-size: 1.5em;" />
			</p>
		</form>
		
			<?php endif; ?>
		<?php endif; ?>
		
	</div>
<?php	
}

require_once($flickr_directory . "/flickr-operations.php");
include_once($flickr_directory . "/flickr-post-editor.php");

add_filter('the_content', 'filter_img_tags');

function filter_img_tags($content) {
	
	$content = preg_replace_callback("/\[img\:(\d+),(.+)\]/",'filter_callback',$content);
	
	return $content;
}

function filter_callback($match) {
	$pid = $match[1];
	$size = $match[2];
	$params = array('photo_id' => $pid, 'auth_token' => $token);
	$photo = flickr_call('flickr.photos.getInfo',$params, true);
	$url = flickr_photo_url($photo['photo'],$size);
	return "<div id=\"image-$pid\" class=\"flickr-img\">
				<a href=\"{$photo['photo']['urls']['url'][0]['_content']}\">
					<img src=\"$url\" alt=\"{$photo['photo']['title']['_content']}\" />
				</a>
			</div>";
}

add_action('wp_head', 'add_flickr_lightbox');

function add_flickr_lightbox() {?>

	<link rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wordpress-flickr-manager/lightbox/lightbox.css" type="text/css" />
	<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wordpress-flickr-manager/lightbox/lightbox.php"></script>
	
<?php }

?>