<?php

add_action('edit_page_form','add_insert_widget');
add_action('edit_form_advanced','add_insert_widget');
add_action('admin_head','add_flickr_styles');

function add_flickr_styles() {
	if(stristr($_SERVER['REQUEST_URI'], 'post.php') === false && stristr($_SERVER['REQUEST_URI'], 'page.php') === false && stristr($_SERVER['REQUEST_URI'], 'post-new.php') === false && stristr($_SERVER['REQUEST_URI'], 'page-new.php') === false) return;
?>
	
	<link rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wordpress-flickr-manager/css/admin_style.css" type="text/css" />
	<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wordpress-flickr-manager/js/flickr-js.php"></script>
	
<?php
}

function add_insert_widget() {
?>
	
	<div class="dbx-box" id="flickr-insert-widget">
	
		<h3 class="dbx-handle">Flickr Manager</h3>
		
		<div id="flickr-content" class="dbx-content">
		
			<div id="flickr-menu">
				<a href="#?faction=upload" title="Upload Photo">Upload Photo</a>
				<a href="#?faction=browse" title="Browse Photos">Browse Photos</a>
				<div id="scope-block">
				<label><input type="radio" name="fscope" id="flickr-personal" value="Personal" checked="checked" /> Personal</label>
				<label><input type="radio" name="fscope" id="flickr-public" value="Public" /> Public</label>
				</div>
				<div style="clear: both; height: 1%;"></div>
			</div>
			<div id="flickr-ajax"></div>
			
		</div>
		
	</div>
	
	<div style="clear: both;">&nbsp;</div>
	
<?php
}

?>