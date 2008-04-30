<?php
ini_set('display_errors', 0);
header('Content-Type: text/javascript');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
require_once("../../../../wp-config.php");
global $flickr_manager;
?>

window.LightboxOptions.fileLoadingImage = "<?php echo $flickr_manager->getAbsoluteUrl(); ?>/images/loading.gif";
window.LightboxOptions.fileBottomNavCloseImage = "<?php echo $flickr_manager->getAbsoluteUrl(); ?>/images/closelabel.gif";

function updateFlickrHref(anchor) {
	var image = anchor.getElementsByTagName('img');
	image = image[0];
	
	if(image.getAttribute("class").match("flickr-original")) {
		anchor.setAttribute("href", image.getAttribute("longdesc"));
	} else {
		var image_link = image.getAttribute("src");
		var testClass = image.getAttribute("class");
		var imageSize = "";
		if(testClass) {
			var testResult = testClass.match(/flickr\-small|flickr\-medium|flickr\-large/);
			switch(testResult.toString()) {
				case "flickr-large":
					imageSize = "_b";
					break;
				case "flickr-medium":
					imageSize = "";
					break;
				case "flickr-small":
					imageSize = "_m";
					break;
			}
		}
		
		if(image_link.match(/[s,t,m]\.jpg/)) {
			image_link = image_link.split("_");
			image_link.pop();
			image_link[image_link.length - 1] = image_link[image_link.length - 1] + imageSize + ".jpg";
			image_link = image_link.join("_");
		} else if(!image_link.match(/b\.jpg/)) {
			image_link = image_link.split(".");
			image_link.pop();
			image_link[image_link.length - 1] = image_link[image_link.length - 1] + imageSize + ".jpg";
			image_link = image_link.join(".");
		}
		anchor.setAttribute("href", image_link);
	}
}



function prepareWFMImages() {
	var anchors = document.getElementsByTagName('a');
	
	// loop through all anchor tags
	for (var i=0; i < anchors.length; i++){
		var anchor = anchors[i];
		
		var relAttribute = String(anchor.getAttribute('rel'));
		
		if (anchor.getAttribute('href') && (relAttribute.toLowerCase().match('flickr-mgr'))){
		
			anchor.onclick = function (event) {
				var save_url = this.getAttribute("href");
				
				updateFlickrHref(this);
				
				event.stop();
				myLightbox.start(this);
				
				var anchors = document.getElementsByTagName('a');
				for (var j=0; j < myLightbox.imageArray.length; j++) {
					for (var i=0; i < anchors.length; i++) {
						var anchor = anchors[i];
						if(anchor.href == myLightbox.imageArray[j][0]) {
							var saveUrl = anchor.getAttribute("href");
							updateFlickrHref(anchor);
							myLightbox.imageArray[j][0] = anchor.getAttribute("href");
							anchor.setAttribute("href", saveUrl);
						}
					}
				}
				
				
				this.setAttribute("href", save_url);
				return false;
			};
		}
		
	}
}

var myLightbox = "";

document.observe('dom:loaded', function() {
	prepareWFMImages();
	myLightbox = new Lightbox();
	myLightbox.imageArray = [];
	myLightbox.activeImage = undefined;
	
	var ids = 'overlay lightbox outerImageContainer imageContainer lightboxImage hoverNav prevLink nextLink loading loadingLink ' + 
			  'imageDataContainer imageData imageDetails caption numberDisplay bottomNav bottomNavClose';   
	$w(ids).each(function(id){ myLightbox[id] = $(id); });
});
