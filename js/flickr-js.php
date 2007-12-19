<?php
ini_set('display_errors', 1);
require_once("../../../../wp-config.php");
header('Content-Type: text/javascript');
?>

/*global document, window, Ajax */

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
	var query = "faction=" + document.getElementById("flickr-action").value + "&photoSize=" + size.options[size.selectedIndex].value + "&filter=" + filter;
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
