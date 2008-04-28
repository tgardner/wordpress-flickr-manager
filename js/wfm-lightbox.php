<?php
ini_set('display_errors', 0);
require_once("../../../../wp-config.php");
header('Content-Type: text/javascript');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
global $flickr_manager;
?>

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

LightboxOptions.fileLoadingImage = "<?php echo $flickr_manager->getAbsoluteUrl(); ?>/images/loading.gif";
LightboxOptions.fileBottomNavCloseImage = "<?php echo $flickr_manager->getAbsoluteUrl(); ?>/images/closelabel.gif";



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
		
			anchor.onclick = function () {
				var save_url = this.getAttribute("href");
				
				updateFlickrHref(this);
				
				var myLightbox = new Lightbox();
				myLightbox.overlay = $('overlay');
				myLightbox.lightbox = $('lightbox');
		        myLightbox.loading = $('loading');
		        myLightbox.lightboxImage = $('lightboxImage');
		        myLightbox.hoverNav = $('hoverNav');
		        myLightbox.prevLink = $('prevLink');
		        myLightbox.nextLink = $('nextLink');
		        myLightbox.imageDataContainer = $('imageDataContainer');
		        myLightbox.numberDisplay = $('numberDisplay');
		        myLightbox.outerImageContainer = $('outerImageContainer');
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



addLoadEvent(prepareWFMImages);
