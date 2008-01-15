<?php
ini_set('display_errors', 0);
require_once("../../../../wp-config.php");
header('Content-Type: text/javascript');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
?>

/*global document, window, Ajax, tinyMCE */

var plugin_dir = "<?php echo get_option('siteurl'); ?>/wp-content/plugins/wordpress-flickr-manager/";

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
	var scope = document.getElementById("flickr-public").value;
	if(document.getElementById("flickr-personal").checked === true) {
		scope = document.getElementById("flickr-personal").value;
	}
	var query = query_array[query_array.length - 1] + "&fscope=" + scope;
	var url = plugin_dir + "flickr-ajax.php";
	displayLoading(destId);
	var flickr_ajax = new Ajax.Updater({success: destId}, url, {method: 'get', parameters: query, onFailure: function(){ returnError(destId); }});
	return false;
}

function performFilter(destId) {
	var filter = document.getElementById("flickr-filter").value;
	var size = document.getElementById("flickr-size");
	var scope = document.getElementById("flickr-public").value;
	var page = document.getElementById("flickr-page").value;
	if(filter != document.getElementById("flickr-old-filter").value) {
		page = 1;
	}
	if(document.getElementById("flickr-personal").checked === true) {
		scope = document.getElementById("flickr-personal").value;
	}
	var query = "faction=" + document.getElementById("flickr-action").value + "&photoSize=" + size.options[size.selectedIndex].value + "&filter=" + filter + "&fpage=" + page + "&fscope=" + scope;
	var url = plugin_dir + "flickr-ajax.php";
	displayLoading(destId);
	var flickr_ajax = new Ajax.Updater({success: destId}, url, {method: 'get', parameters: query, onFailure: function(){ returnError(destId); }});
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
			return executeLink(this, destId);
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

function insertImage(image,owner,id,name) {
	if ( typeof tinyMCE != 'undefined' ) {
		var imgHTML = "";
		if(document.getElementById("flickr-lightbox").checked) {
			imgHTML = '<a href="http://www.flickr.com/photos/' + owner + "/" + id + '/" rel="flickr-mgr">';
			imgHTML = imgHTML + '<img src="' + image.src + '" alt="' + image.alt + '" /></a>';
		} else {
			imgHTML = '<a href="http://www.flickr.com/photos/' + owner + "/" + id + '/" title="' + image.alt + '">';
			imgHTML = imgHTML + '<img src="' + image.src + '" alt="' + image.alt + '" /></a>';
		}
		var license = document.getElementById("license-" + id);
		if(license) {
			imgHTML = imgHTML + "<br /><small><a href='" + license.href + "' title='" + license.title + "' rel='license'>" + license.innerHTML + "</a> by <a href='http://www.flickr.com/people/"+owner+"/'>"+name+"</a></small>&nbsp;";
		}
		var i = tinyMCE.getInstanceById('content');
		if(typeof i ==  'undefined') {
			return false;
		}
		i.contentWindow.focus();
		tinyMCE.execCommand('mceInsertContent',false,imgHTML);
	}
	return false;
}
