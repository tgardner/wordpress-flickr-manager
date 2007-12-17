<?php
	
	ini_set('display_errors',1);
	require_once(dirname(__FILE__) . "/flickr-operations.php");
	require_once("../../../wp-config.php");
	require_once("../../../wp-includes/wp-db.php");
	
	$flickr_table = $wpdb->prefix . "flickr";

	$action = $_REQUEST['action'];
	
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
		
		$page = (isset($_REQUEST['fpage'])) ? $_REQUEST['fpage'] : '1';
		$per_page = (isset($_REQUEST['fper_page'])) ? $_REQUEST['fper_page'] : '5';
		$nsid = $wpdb->get_var("SELECT value FROM $flickr_table WHERE name='nsid'");
		$params = array('user_id' => $nsid, 'per_page' => $per_page, 'page' => $page, 'auth_token' => $token, 'extras' => 'original_format');
		$size = (isset($_REQUEST['photoSize']) && !empty($_REQUEST['photoSize'])) ? $_REQUEST['photoSize'] : "thumbnail";
		if(isset($_REQUEST['filter']) && !empty($_REQUEST['filter'])) {
			$params = array_merge($params,array('tags' => $_REQUEST['filter'],'tag_mode' => 'all'));
		}
		$photos = flickr_call('flickr.photos.search', $params, true);
		$pages = $photos['photos']['pages'];
	?>
		
		<div id="flickr-browse">
		
			<?php foreach ($photos['photos']['photo'] as $photo) : ?>
	
			<div class="flickr-img" id="flickr-<?php echo $photo['id']; ?>">
				<img src="<?php echo flickr_photo_url($photo,$size); ?>" alt="<?php echo $photo['title']; ?>" />
			</div>
	
			<?php endforeach; ?>
		
		</div>
			
		<div style="clear: both;">&nbsp;</div>
		<div id="flickr-nav">
			
				<?php if($page > 1) :?>
				
				<a href="#?action=<?php echo $_REQUEST['action']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=1" title="&laquo; First Page" onclick="executeLink(this)">&laquo;</a>&nbsp;
				<a href="#?action=<?php echo $_REQUEST['action']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $page - 1; ?>" title="&lsaquo; Previous Page" onclick="executeLink(this)">&lsaquo;</a>&nbsp;
				
				<?php endif; ?>
				
				<label>Filter: 
				<input type="text" name="filter" id="flickr-filter" value="<?php echo $_REQUEST['filter']; ?>" />
				</label>
				<input type="hidden" name="action" id="flickr-action" value="<?php echo $_REQUEST['action']; ?>" />
				<input type="submit" name="button" value="Filter" onclick="performFilter()" />
				
				<?php if($page < $pages) :?>
				
				&nbsp;<a href="#?action=<?php echo $_REQUEST['action']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $page + 1; ?>" title="Next Page &rsaquo;" onclick="executeLink(this)">&rsaquo;</a>
				&nbsp;<a href="#?action=<?php echo $_REQUEST['action']; ?>&amp;filter=<?php echo $_REQUEST['filter']; ?>&amp;fpage=<?php echo $pages; ?>" title="Last Page &raquo;" onclick="executeLink(this)">&raquo;</a>
				
				<?php endif; ?>
				<br>
				<?php $sizes = array("square", 'thumbnail', 'small', 'medium', 'original'); ?>
				<select name="photoSize" id="flickr-size">
				
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

		echo '<iframe id="flickr-uploader" name="flickr-uploader" src="/wp-content/plugins/flickr/upload.php"></iframe>';
	
	}
	
?>
