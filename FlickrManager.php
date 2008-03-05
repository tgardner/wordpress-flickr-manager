<?php
/*
Plugin Name: Flickr Manager
Plugin URI: http://tgardner.net/
Description: Handles uploading, modifying images on Flickr, and insertion into posts.
Version: 1.5.2
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

$version = explode(".",phpversion()); 

if(intval($version[0]) < 5 && intval($version[1]) < 4) {
	$filename = explode("/", __FILE__);
	$plugin = "{$filename[count($filename) - 2]}/{$filename[count($filename) - 1]}";
	echo "<b>ERROR: You're currently running " . phpversion() . " and you must have at least PHP 4.4.x in order to use Flickr Manager!</b>";
	return;
} 

if(class_exists('FlickrManager')) return;
require_once(dirname(__FILE__) . "/FlickrCore.php");

class FlickrManager extends FlickrCore {
	
	var $db_version;
	var $db_table;
	var $plugin_directory;
	var $plugin_filename;
	
	
	
	function FlickrManager() {
		global $wpdb;
		
		$this->db_table = $wpdb->prefix . "flickr";
		$this->db_version = '1.0';
		
		$filename = explode("/", __FILE__);
		$this->plugin_directory = $filename[count($filename) - 2];
		$this->plugin_filename = $filename[count($filename) - 1];
		add_action("activate_$this->plugin_directory/$this->plugin_filename", array(&$this, 'install'));
		
		add_action('admin_menu', array(&$this, 'add_menus'));
		add_action('wp_head', array(&$this, 'add_headers'));
		add_action('admin_head', array(&$this, 'add_admin_headers'));
		add_action('edit_page_form', array(&$this, 'add_flickr_panel'));
		add_action('edit_form_advanced', array(&$this, 'add_flickr_panel'));
		
		add_filter('the_content', array(&$this, 'filterContent'));
	}
	
	
	
	function install() {
		global $wpdb;
		
		if($wpdb->get_var("SHOW TABLES LIKE '$this->db_table'") != $this->db_table) {
			/* Create Table */
			
			$sql = "CREATE TABLE $this->db_table (
					uid mediumint(9) NOT NULL AUTO_INCREMENT,
					name VARCHAR(55) NOT NULL,
					value VARCHAR(55),
					UNIQUE KEY id (uid)
					);";
			
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sql);
		
			add_option("flickr_db_version", $this->db_version);
		}
	}
	
	
	
	function add_menus() {
		// Add a new submenu under Options
		add_options_page('Flickr Options', 'Flickr', 5, __FILE__, array(&$this, 'options_page'));
		
		// Add a new submenu under Manage
		add_management_page('Flickr Management', 'Flickr', 5, __FILE__, array(&$this, 'manage_page'));
	}
	
	
	
	function options_page() {
		global $wpdb;
		if(isset($_REQUEST['action'])) {
			switch($_REQUEST['action']) {
				case 'token':
					if($frob = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='frob'")) {
						$token = $this->call('flickr.auth.getToken', array('frob' => $frob), true);
						
						if($token['stat'] == 'ok') {
							
							$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='token'");
							if(empty($exists)) {
								$sql = "INSERT INTO $this->db_table (name, value) VALUES ('token', '{$token['auth']['token']['_content']}')";
							} else {
								$sql = "UPDATE $this->db_table SET value='{$token['auth']['token']['_content']}' WHERE name='token'";
							}
							$wpdb->query($sql);
							
							$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='nsid'");
							if(empty($exists)) {
								$sql = "INSERT INTO $this->db_table (name,value) VALUES ('nsid','{$token['auth']['user']['nsid']}')";
							} else {
								$sql = "UPDATE $this->db_table SET value='{$token['auth']['user']['nsid']}' WHERE name='nsid'";
							}
							$wpdb->query($sql);
							
							$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='username'");
							if(empty($exists)) {
								$sql = "INSERT INTO $this->db_table (name,value) VALUES ('username','{$token['auth']['user']['username']}')";
							} else {
								$sql = "UPDATE $this->db_table SET value='{$token['auth']['user']['username']}' WHERE name='username'";
							}
							$wpdb->query($sql);
							
						}
					}
					break;
				
				case 'logout':
					$sql = "DELETE FROM $this->db_table";
					$wpdb->query($sql);
					break;
					
				case 'save':
					$_REQUEST['fper_page'] = (!isset($_REQUEST['fper_page']) || !is_numeric($_REQUEST['fper_page']) || intval($_REQUEST['fper_page']) <= 0) ? 5 : $_REQUEST['fper_page'];
					
					$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='per_page'");
					if(empty($exists)) {
						$sql = "INSERT INTO $this->db_table (name,value) VALUES ('per_page','{$_REQUEST['fper_page']}')";
					} else {
						$sql = "UPDATE $this->db_table SET value='{$_REQUEST['fper_page']}' WHERE name='per_page'";
					}
					$wpdb->query($sql);
					
					$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='new_window'");
					if(empty($exists)) {
						$sql = "INSERT INTO $this->db_table (name,value) VALUES ('new_window','{$_REQUEST['fnew_window']}')";
					} else {
						$sql = "UPDATE $this->db_table SET value='{$_REQUEST['fnew_window']}' WHERE name='new_window'";
					}
					$wpdb->query($sql);
					
					$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='lightbox_default'");
					if(empty($exists)) {
						$sql = "INSERT INTO $this->db_table (name,value) VALUES ('lightbox_default','{$_REQUEST['flbox_default']}')";
					} else {
						$sql = "UPDATE $this->db_table SET value='{$_REQUEST['flbox_default']}' WHERE name='lightbox_default'";
					}
					$wpdb->query($sql);
					
					$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='lightbox_enable'");
					if(empty($exists)) {
						$sql = "INSERT INTO $this->db_table (name,value) VALUES ('lightbox_enable','{$_REQUEST['flightbox_enable']}')";
					} else {
						$sql = "UPDATE $this->db_table SET value='{$_REQUEST['flightbox_enable']}' WHERE name='lightbox_enable'";
					}
					$wpdb->query($sql);
					
					if($_REQUEST['fbrowse_check'] == "true") {
						$browse_size = $_REQUEST['fbrowse_size'];
						$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='browse_size'");
						
						if(empty($exists)) {
							$sql = "INSERT INTO $this->db_table (name, value) VALUES ('browse_size', '{$_REQUEST['fbrowse_size']}'),('browse_check', '{$_REQUEST['fbrowse_check']}')";
							$wpdb->query($sql);
						} else {
							$sql = "UPDATE $this->db_table SET value='{$_REQUEST['fbrowse_size']}' WHERE name='browse_size'";
							$wpdb->query($sql);
							$sql = "UPDATE $this->db_table SET value='{$_REQUEST['fbrowse_check']}' WHERE name='browse_check'";
							$wpdb->query($sql);
						}
						
					}
					
					break;
					
			}
		}
		
		if(($token = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='token'"))) {
			$auth_status = $this->call('flickr.auth.checkToken', array('auth_token' => $token), true);
		}
		?>
		
		<div class="wrap">
			<h2>Flickr Options</h2>
			<?php if(empty($token) || $auth_status['stat'] != 'ok') : ?>
			
			<!-- Begin Authentication -->
			
			<?php
			$frob = $this->call('flickr.auth.getFrob', array(), true);
			$frob = $frob['frob']['_content'];
			$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='frob'");
			if(empty($exists)) {
				$sql = "INSERT INTO $this->db_table (name,value) VALUES ('frob','$frob')";
			} else {
				$sql = "UPDATE $this->db_table SET value='$frob' WHERE name='frob'";
			}
			$wpdb->query($sql);
			?>
			
			<div align="center">
				<h3>Step 1:</h3>
				<form>
					<input type="button" value="Authenticate" onclick="window.open('<?php echo $this->getAuthUrl($frob,'delete'); ?>')" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
				</form>
				
				<h3>Step 2:</h3>
				<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<input type="hidden" name="action" value="token" />
					<input type="submit" name="Submit" value="<?php _e('Finish &raquo;') ?>" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
				</form>
			</div>
			
			<?php else : ?>
			
			<!-- Display options -->
			<div align="center">
				<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<input type="hidden" name="action" value="logout" />
					<input type="submit" name="Submit" value="<?php _e('Logout &raquo;') ?>" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
				</form>
			</div>
			
				<?php
				$nsid = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='nsid'");
				$info = $this->call('flickr.people.getInfo',array('user_id' => $nsid));
				$exists = $wpdb->get_var("SELECT COUNT(value) FROM $this->db_table WHERE name='is_pro'");
				if(empty($exists)) {
					$sql = "INSERT INTO $this->db_table (name,value) VALUES ('is_pro','{$info['person']['ispro']}')";
				} else {
					$sql = "UPDATE $this->db_table SET value='{$info['person']['ispro']}' WHERE name='is_pro'";
				}
				$wpdb->query($sql);
				if($info['stat'] == 'ok') :
				?>
				
				<h3>User Information <?php if($info['person']['ispro'] != 0) echo '<img src="' . $this->getAbsoluteUrl() . '/images/badge_pro.gif" alt="pro" style="vertical-align: middle;" />'; ?></h3>
		
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
				$exists = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='per_page'");
				if(!empty($exists)) $_REQUEST['fper_page'] = $exists;
				
				$_REQUEST['fnew_window'] = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='new_window'");
				$_REQUEST['flightbox_enable'] = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='lightbox_enable'");
				$_REQUEST['fbrowse_size'] = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='browse_size'");
				$_REQUEST['fbrowse_check'] = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='browse_check'");
				
				$exists = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='lightbox_default'");
				if(!empty($exists)) $_REQUEST['flbox_default'] = $exists;
				else $_REQUEST['flbox_default'] = "medium";
				?>
				
				<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<input type="hidden" name="action" value="save" />
					
					<div>
					<strong>Miscellaneous</strong><br />
					<label>Images per page: <input type="text" name="fper_page" value="<?php echo $_REQUEST['fper_page']; ?>" style="padding: 3px; width: 50px;" /></label>
					<br /><label><input type="checkbox" name="fnew_window" value="true" style="margin: 5px 0px;" <?php if($_REQUEST['fnew_window'] == "true") echo 'checked="checked" '; ?>/> Add target="_blank" to image page links.</label>
					
					<br />
					<label><input type="checkbox" name="fbrowse_check" value="true" style="margin: 5px 0px;" <?php if($_REQUEST['fbrowse_check'] == "true") echo 'checked="checked" '; ?>/> Limit size of images in the browse window to</label> 
					<select name="fbrowse_size">
						<option value="square" <?php if($_REQUEST['fbrowse_size'] == "square") echo 'selected="selected"'; ?>>Square</option>
						<option value="thumbnail" <?php if($_REQUEST['fbrowse_size'] == "thumbnail") echo 'selected="selected"'; ?>>Thumbnail</option>
					</select>
					</div>
					
					<br />
					<div>
					<strong>Lightbox</strong><br />
					<label><input type="checkbox" name="flightbox_enable" value="true" <?php if($_REQUEST['flightbox_enable'] == "true") echo 'checked="checked" '; ?>/> Enable lightbox support by default</label><br />
					<label>Default lightbox picture: <select name="flbox_default">
					<?php
						$sizes = array("small","medium","large");
						foreach ($sizes as $size) {
							echo "<option value=\"$size\"";
							if($_REQUEST['flbox_default'] == $size) echo ' selected="selected" ';
							echo ">" . ucfirst($size) . "</option>\n";
						}
					?>
					</select></label>
					</div>
					
					<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Submit') ?> &raquo;" style="font-size: 1.5em;" />
					</p>
				</form>
				
			<?php 
				endif;
			endif; 
			?>
			
			<p>&nbsp;</p>
		
			<div style="text-align:center">
				
				<b>This plugin takes a great deal of time and effort developing, so please if you like the plugin feel free to donate!</b>
				
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="text-align: center;">
					<input type="hidden" name="cmd" value="_s-xclick" />
					<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" style="border: 0px; background: none;" />
					<img alt="" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1" />
					<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
				</form>
			</div>
			
		</div>
		
		<?php
	}
	
	
	
	function manage_page() {
		global $wpdb;
		$token = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='token'");
		if(empty($token)) {
			echo '<div class="wrap"><h3>Error: Please authenticate through <a href="'.get_option('siteurl')."/wp-admin/options-general.php?page=$this->plugin_directory/$this->plugin_filename\">Options->Flickr</a></h3></div>\n";
			return;
		} else {
			$auth_status = $this->call('flickr.auth.checkToken', array('auth_token' => $token), true);
			if($auth_status['stat'] != 'ok') {
				echo '<div class="wrap"><h3>Error: Please authenticate through <a href="'.get_option('siteurl')."/wp-admin/options-general.php?page=$this->plugin_directory/$this->plugin_filename\">Options->Flickr</a></h3></div>\n";
				return;
			}
		}
		
		switch($_REQUEST['action']) {
			case 'upload':
				/* Perform file upload */
				if($_FILES['uploadPhoto']['error'] == 0) {
					
					$params = array('auth_token' => $token, 'photo' => '@'.$_FILES['uploadPhoto']['tmp_name']);
					$rsp = $this->upload($params);
					
					if($rsp !== false) {
					
						$xml_parser = xml_parser_create();
						xml_parse_into_struct($xml_parser, $rsp, $vals, $index);
						xml_parser_free($xml_parser);
						
						$pid = $vals[$index['PHOTOID'][0]]['value'];
						
						if(!empty($pid)) {
							$_REQUEST['pid'] = $pid;
							$_REQUEST['action'] = 'edit';
						}
						
					}
					
				}
				break;
			
			case 'modify':
				/* Perform modify */
				$params = array('photo_id' => $_REQUEST['pid'], 
								'title' => $_REQUEST['ftitle'],
								'description' => $_REQUEST['description'],
								'auth_token' => $token);
				
				$this->post('flickr.photos.setMeta', $params, true);
				
				$params = array('photo_id' => $_REQUEST['pid'], 
								'tags' => $_REQUEST['tags'],
								'auth_token' => $token);
				
				$this->post('flickr.photos.setTags', $params, true);
				
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
				
				$this->post('flickr.photos.setPerms', $params, true);
				
				$_REQUEST['action'] = 'default';
				break;
				
			case 'delete': 
				/* Perform delete */
				$params = array('auth_token' => $token, 'photo_id' => $_REQUEST['pid']);
				$this->post('flickr.photos.delete', $params, true);
				
				$_REQUEST['action'] = 'default';
				break;
		}
		?>
		
		<div class="wrap">
	
			<h2>Image Management</h2>
			
			<form enctype="multipart/form-data" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="padding: 0px 20px;">
				<h3>Upload Photo</h3>
				
				<p class="submit" style="text-align: left;">
					<label>Upload Photo:
						<input type="file" name="uploadPhoto" id="uploadPhoto" />
					</label>
					<input type="submit" name="Submit" value="<?php _e('Upload &raquo;') ?>" />
					<input type="hidden" name="action" value="upload" />
				</p>
			</form>
			
			<div style="padding: 0px 20px;">
				
				<?php
				switch($_REQUEST['action']) {
					case 'edit':
						$params = array('photo_id' => $_REQUEST['pid'], 'auth_token' => $token);
						$photo = $this->call('flickr.photos.getInfo',$params, true);
						?>
						
						<h3>Modify Photo</h3>
						<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}"; ?>" >&laquo; Back</a><br /><br />
						
						<!-- Begin modification of inidividual photo -->
						
						<div align="center">
							<img src="<?php echo $this->getPhotoUrl($photo['photo'],"medium"); ?>" alt="<?php echo $photo['photo']['title']['_content']; ?>" /><br />
						
						
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
								<input type="hidden" name="action" value="modify" />
								<input type="hidden" name="pid" value="<?php echo $_REQUEST['pid']; ?>" />
								<input type="submit" name="submit" value="Submit" />
								<input type="reset" name="reset" value="Reset" />
							</form>
						</div>
						
						<?php
						break;
						
					default:
						$page = (isset($_REQUEST['fpage'])) ? $_REQUEST['fpage'] : '1';
						$per_page = (isset($_REQUEST['fper_page'])) ? $_REQUEST['fper_page'] : '10';
						$nsid = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='nsid'");
						$params = array('user_id' => $nsid, 'per_page' => $per_page, 'page' => $page, 'auth_token' => $token);
						$photos = $this->call('flickr.photos.search', $params, true);
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
								<td align="center"><img src="<?php echo $this->getPhotoUrl($photo,"square"); ?>" alt="<?php echo $photo['title']; ?>" /></td>
								<td align="center"><?php echo $photo['title']; ?></td>
								<td align="center"><a href="http://www.flickr.com/photos/<?php echo "$nsid/{$photo['id']}/"; ?>" target="_blank">View</a> / 
								<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;action=edit&amp;pid={$photo['id']}"; ?>">Modify</a> / 
								<a href="<?php echo "{$_SERVER['PHP_SELF']}?page={$_REQUEST['page']}&amp;action=delete&amp;pid={$photo['id']}"; ?>" onclick="return confirm('Are you sure you want to delete this?');">Delete</a>
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
				}
				?>
			</div>
			
		</div>
		
		<?php
	}
	
	
	
	function filterContent($content) {
		$content = preg_replace_callback("/\[img\:(\d+),(.+)\]/", array(&$this, 'filterCallback'), $content);
		$content = preg_replace_callback("/\[imgset\:(\d+),(.+),(.+)\]/", array(&$this, 'filterSets'), $content);
		return $content;
	}
	
	
	
	function filterSets($match) {
		global $wpdb;
		$setid = $match[1];
		$size = $match[2];
		$lightbox = $match[3];
		$lightbox = ($lightbox == "true") ? true : false;
		$token = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='token'");
		$params = array('photoset_id' => $setid, 'auth_token' => $token, 'extras' => 'original_format');
		$photoset = $this->call('flickr.photosets.getPhotos',$params, true);
		
		/* echo '<pre>'; var_dump($photoset); echo '</pre>'; */
		
		foreach ($photoset['photoset']['photo'] as $photo) {
			$replace .= "<a href=\"http://www.flickr.com/photos/{$photoset['photoset']['owner']}/{$photo['id']}/\" title=\"{$photo['title']}\" ";
			if($lightbox) $replace .= "rel=\"flickr-mgr[$setid]\" ";
			$replace .= "class=\"flickr-image\" >\n";
			$replace .= '	<img src="' . $this->getPhotoUrl($photo,$size) . "\" alt=\"{$photo['title']}\" ";
			if($lightbox) $replace .= 'class="flickr-medium" ';
			$replace .= "/>\n";
			$replace .= "</a>\n";
		}
		return $replace;
	}
	
	
	
	function filterCallback($match) {
		global $wpdb;
		$pid = $match[1];
		$size = $match[2];
		$token = $wpdb->get_var("SELECT value FROM $this->db_table WHERE name='token'");
		$params = array('photo_id' => $pid, 'auth_token' => $token);
		$photo = $this->call('flickr.photos.getInfo',$params, true);
		$url = $this->getPhotoUrl($photo['photo'],$size);
		return "<div id=\"image-$pid\" class=\"flickr-img\">
					<a href=\"{$photo['photo']['urls']['url'][0]['_content']}\">
						<img src=\"$url\" alt=\"{$photo['photo']['title']['_content']}\" />
					</a>
				</div>";
	}
	
	
	
	function add_headers() {
	?>
		
		<link rel="stylesheet" href="<?php echo $this->getAbsoluteUrl(); ?>/lightbox/css/lightbox.css" type="text/css" />
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/prototype.js"></script>
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/scriptaculous/scriptaculous.js?load=effects"></script>
		<script type="text/javascript" src="<?php echo $this->getAbsoluteUrl(); ?>/lightbox/lightbox.php"></script>
		
	<?php
	}
	
	
	
	function getAbsoluteUrl() {
		return get_option('siteurl') . "/wp-content/plugins/" . $this->plugin_directory;
	}
	
	
	
	function add_admin_headers() {
		$filename = explode("/", $_SERVER['REQUEST_URI']);
		$filename = $filename[count($filename) - 1];
		if($end = strpos($filename,"?")) $filename = substr($filename,0,$end);
		$filename = strtolower($filename);
		
		if($filename != "post.php" && $filename != "page.php" && $filename != "post-new.php" && $filename != "page-new.php") return;
	?>
		
		<link rel="stylesheet" href="<?php echo $this->getAbsoluteUrl(); ?>/css/admin_style.css" type="text/css" />
		<script type="text/javascript" src="<?php echo $this->getAbsoluteUrl(); ?>/js/flickr-js.php"></script>
		
	<?php
	}
	
	
	
	function add_flickr_panel() {
	?>

		<div class="dbx-box" id="flickr-insert-widget">
		
			<h3 class="dbx-handle">Flickr Manager</h3>
			
			<div id="flickr-content" class="dbx-content">
			
				<div id="flickr-menu">
					<a href="#?faction=upload" title="Upload Photo">Upload Photo</a>
					<a href="#?faction=browse" id="fbrowse-photos" title="Browse Photos">Browse Photos</a>
					<div id="scope-block">
					<label><input type="radio" name="fscope" id="flickr-personal" value="Personal" checked="checked" onchange="executeLink(document.getElementById('fbrowse-photos'),'flickr-ajax');" /> Personal</label>
					<label><input type="radio" name="fscope" id="flickr-public" value="Public" onchange="executeLink(document.getElementById('fbrowse-photos'),'flickr-ajax');" /> Public</label>
					</div>
					<div style="clear: both; height: 1%;"></div>
				</div>
				<div id="flickr-ajax"></div>
				
			</div>
			
		</div>
		
		<div style="clear: both;">&nbsp;</div>
		
	<?php
	}
	
}

global $flickr_manager;
$flickr_manager = new FlickrManager();

?>
