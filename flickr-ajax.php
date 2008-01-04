<?php
	
	ini_set('display_errors',1);
	require_once(dirname(__FILE__) . "/flickr-operations.php");
	require_once("../../../wp-config.php");
	require_once("../../../wp-includes/wp-db.php");
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	
	$curr_user = wp_get_current_user();
	if($curr_user->user_level < 2) die("Unauthorized Access");
	
	$flickr_table = $wpdb->prefix . "flickr";

	$action = $_REQUEST['faction'];
	
	switch($action) {
		case 'browse':
			displayBrowse();
			break;
		case 'upload':
			displayUpload();
			break;
	}
	
	
	
	
	function displayBrowse() {
		global $wpdb, $flickr_table;
		$token = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='token'");
		
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
		
		$page = (isset($_REQUEST['fpage'])) ? $_REQUEST['fpage'] : '1';
		$per_page = (isset($_REQUEST['fper_page'])) ? $_REQUEST['fper_page'] : '5';
		$nsid = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='nsid'");
		$fscope = $_REQUEST['fscope'];
		$params = array('per_page' => $per_page, 'page' => $page, 'auth_token' => $token, 'extras' => 'license,owner_name,original_format');
		if($fscope == "Personal") {
			$params = array_merge($params, array('user_id' => $nsid));
		} else {
			$licences = flickr_call('flickr.photos.licenses.getInfo',array());
			$temp = array();
			for($i = 1; $i < count($licences['licenses']['license']); $i++) {
				array_push($temp,$i);
			}
			$licence_search = implode(',',$temp);
		}
		$size = (isset($_REQUEST['photoSize']) && !empty($_REQUEST['photoSize'])) ? $_REQUEST['photoSize'] : "thumbnail";
		$flickr_function = 'flickr.photos.search';
		if(isset($_REQUEST['filter']) && !empty($_REQUEST['filter'])) {
			$params = array_merge($params,array('tags' => $_REQUEST['filter'],'tag_mode' => 'all'));
		} elseif($fscope == "Public") {
			//$flickr_function = 'flickr.photos.getRecent';
			$params = array_merge($params,array('text' => " "));
		}
		if($fscope == "Public" && $flickr_function == 'flickr.photos.search') {
			$params = array_merge($params, array('license' => $licence_search));
		}
		$photos = flickr_call($flickr_function, $params, true);
		$pages = $photos['photos']['pages'];
	?>
		
		<div id="flickr-browse">
			
			<?php foreach ($photos['photos']['photo'] as $photo) : ?>
	
			<div class="flickr-img" id="flickr-<?php echo $photo['id']; ?>">
				<!-- <a href="http://www.flickr.com/photos/<?php echo "{$photo['owner']}/{$photo['id']}/"; ?>" title="<?php echo $photo['title']; ?>"> -->
				
					<img src="<?php echo flickr_photo_url($photo,$size); ?>" alt="<?php echo $photo['title']; ?>" onclick="return insertImage(this,'<?php echo $photo['owner']; ?>','<?php echo $photo['id']; ?>','<?php echo str_replace("'","&lsquo;",$photo['ownername']); ?>')" />
					
					<?php 
					if($fscope == "Public") {
						foreach ($licences['licenses']['license'] as $licence) {
							if($licence['id'] == $photo['license']) {
								if($licence['url'] == '') $licence['url'] = "http://www.flickr.com/people/{$photo['owner']}/";
								echo "<br /><small><a href='{$licence['url']}' title='{$licence['name']}' rel='license' id='license-{$photo['id']}' onclick='return false'><img src='".get_option('home')."/wp-content/plugins/wordpress-flickr-manager/images/creative_commons_bw.gif' alt='{$licence['name']}'/></a> by {$photo['ownername']}</small>";
							}
						}
					}
					?>
					
				<!-- </a> -->
			</div>
	
			<?php endforeach; ?>
		
		</div>
			
		<div style="clear: both;">&nbsp;</div>
		<div id="flickr-nav">
			
				<?php if($page > 1) :?>
				
				<a href="#?faction=<?php echo $_REQUEST['faction']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=1&amp;photoSize=<?php echo $_REQUEST['photoSize']; ?>" title="&laquo; First Page" onclick="return executeLink(this,'flickr-ajax')">&laquo;</a>&nbsp;
				<a href="#?faction=<?php echo $_REQUEST['faction']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $page - 1; ?>&amp;photoSize=<?php echo $_REQUEST['photoSize']; ?>" title="&lsaquo; Previous Page" onclick="return executeLink(this,'flickr-ajax')">&lsaquo;</a>&nbsp;
				
				<?php endif; ?>
				
				<label>Filter: 
				<input type="text" name="filter" id="flickr-filter" value="<?php echo $_REQUEST['filter']; ?>" />
				</label>
				<input type="hidden" name="faction" id="flickr-action" value="<?php echo $_REQUEST['faction']; ?>" />
				<input type="hidden" name="fpage" id="flickr-page" value="<?php echo $_REQUEST['fpage']; ?>" />
				<input type="hidden" name="fold_filter" id="flickr-old-filter" value="<?php echo $_REQUEST['filter']; ?>" />
				<input type="submit" name="button" value="Filter" onclick="return performFilter('flickr-ajax')" />
				
				<?php if($page < $pages) :?>
				
				&nbsp;<a href="#?faction=<?php echo $_REQUEST['faction']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $page + 1; ?>&amp;photoSize=<?php echo $_REQUEST['photoSize']; ?>" title="Next Page &rsaquo;" onclick="return executeLink(this,'flickr-ajax')">&rsaquo;</a>
				&nbsp;<a href="#?faction=<?php echo $_REQUEST['faction']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $pages; ?>&amp;photoSize=<?php echo $_REQUEST['photoSize']; ?>" title="Last Page &raquo;" onclick="return executeLink(this,'flickr-ajax')">&raquo;</a>
				
				<?php endif; ?>
				<br>
				<?php $sizes = array("square", 'thumbnail', 'small', 'medium', 'original'); ?>
				<select name="photoSize" id="flickr-size" onchange="return performFilter('flickr-ajax')">
				
					<?php 
					foreach ($sizes as $v) {
						echo '<option value="' . strtolower($v) . '" ';
						if($v == $size) echo 'selected="selected" ';
						echo '>' . ucfirst($v) . "</option>\n";
					}
					?>
					
				</select>
		</div>
		
<?php
	}
	
	
	
	
	function displayUpload() {
		global $wpdb, $flickr_table;
		$token = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='token'");
		
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

		echo '<iframe id="flickr-uploader" name="flickr-uploader" src="'.get_option('home').'/wp-content/plugins/wordpress-flickr-manager/upload.php"></iframe>';
	
	}
	
?>
