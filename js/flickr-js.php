<?php
ini_set('display_errors', 0);
require_once("../../../../wp-config.php");
header('Content-Type: text/javascript');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
?>

/*global document, window, Ajax, tinyMCE */

var plugin_dir = "<?php echo get_option('home'); ?>/wp-content/plugins/wordpress-flickr-manager/";

function displayLoading(destId) {
	var element = document.getElementById(destId);
	if(!element) {
		return;
	}
	var image = document.createElement("img");
	image.setAttribute("alt", "loading...");
	image.setAttribute("src", plugin_dir + "images/loading.gif");
	image.className = "loading";
	element.innerHTML = "";
	element.appendChild(image);
}

function returnError(destId) {
	var element = document.getElementById(destId);
	if(!element) {
		return;
	}
	element.innerHTML = "Unexpected error occured while performing an AJAX request";
}

function executeLink(link, destId) {
	var query_array = link.getAttribute("href").split("?");
	var query = query_array[query_array.length - 1];
	var url = plugin_dir + "flickr-ajax.php";
	displayLoading(destId);
	var flickr_ajax = new Ajax.Updater({success: destId}, url,	{method: 'get', parameters: query, onFailure: function(){ returnError(destId); }});
	return false;
}

function performFilter(destId) {
	var filter = document.getElementById("flickr-filter").value;
	var size = document.getElementById("flickr-size");
	var query = "faction=" + document.getElementById("flickr-action").value + "&photoSize=" + size.options[size.selectedIndex].value + "&filter=" + filter + "&fpage=" + document.getElementById("flickr-page").value;
	var url = plugin_dir + "flickr-ajax.php";
	displayLoading(destId);
	var flickr_ajax = new Ajax.Updater({success: destId}, url,	{method: 'get', parameters: query, onFailure: function(){ returnError(destId); }});
	return false;
}

function prepareLinks(containId, destId) {
	if (!document.getElementById || !document.getElementsByTagName) {
		return;
	}
	if (!document.getElementById(containId)) {
		return;
	}
	var list = document.getElementById(containId);
	var links = list.getElementsByTagName("a");
	for (var i=0; i < links.length; i++) {
		links[i].onclick = function() {
			var query_array = this.getAttribute("href").split("?");
			var query = query_array[query_array.length - 1];
			var url = plugin_dir + "flickr-ajax.php";
			displayLoading(destId);
			var flickr_ajax = new Ajax.Updater({success: destId}, url,	{method: 'get', parameters: query, onFailure: function(){ returnError(destId); }});
			return false;
		};
	}
}

function addLoadEvent(func) {
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function() {
			oldonload();
			func();
		};
	}
}

addLoadEvent(function () {
	prepareLinks('flickr-content','flickr-ajax');
});

function insertImage(image,owner,id) {
	if ( typeof tinyMCE != 'undefined' ) {
		var imgHTML = '<a href="http://www.flickr.com/photos/' + owner + "/" + id + '/" title="' + image.alt + '">';
		imgHTML = imgHTML + '<img src="' + image.src + '" alt="' + image.alt + '" /></a>&nbsp;';
		
		var i = tinyMCE.getInstanceById('content');
		if(typeof i ==  'undefined') {
			return false;
		}
		i.contentWindow.focus();
		tinyMCE.execCommand('mceInsertContent',false,imgHTML);
	}
	return false;
}
