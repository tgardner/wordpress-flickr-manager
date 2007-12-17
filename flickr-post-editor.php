<?php

add_action('edit_page_form','add_insert_widget');
add_action('edit_form_advanced','add_insert_widget');
add_action('admin_head','add_flickr_styles');

function add_flickr_styles() {
	if(isset($_REQUEST['page'])) return;
?>
	
	<link rel="stylesheet" href="<?php bloginfo( 'wpurl' ); ?>/wp-content/plugins/flickr/css/admin_style.css" type="text/css" />
	<script type="text/javascript" src="<?php bloginfo( 'wpurl' ); ?>/wp-content/plugins/flickr/js/ajax.js"></script>
	
<?php
}

function add_insert_widget() {
?>
	
	<div class="dbx-box" id="flickr-insert-widget">
	
		<h3 class="dbx-handle">Flickr Manager</h3>
		
		<div id="flickr-content" class="dbx-content">
		
			<div id="flickr-menu">
				<a href="#?action=upload" title="Upload Photo">Upload Photo</a>
				<a href="#?action=browse" title="Browse Photos">Browse Photos</a>
				<div style="clear: both; height: 1%;"></div>
			</div>
			<div id="flickr-ajax"></div>
			
		</div>
		
	</div>
	
	<div style="clear: both;">&nbsp;</div>
	
<?php
}

?>