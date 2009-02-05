<?php
header('Content-Type: text/javascript');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
require_once("../../../../wp-config.php");
?>
var mouseX = mouseY = 0; 
jQuery(document).ready(function() {
	
	jQuery().mousemove(function(e) { mouseX = e.pageX; mouseY = e.pageY; });
	
	jQuery("a#wfm-entire-set").hide();
	
	jQuery("#flickr-form").submit(function() { return false; });
	
	jQuery("#wfm-insert-set").change(function() {
		if (jQuery('#wfm-insert-set').is(':checked')) {
			jQuery("#wfm-set-name").focus();
		}
	});
	
	jQuery("#wfm-entire-set").click(function(e) {
		
		var wrapper = jQuery('<div class="sizePicker"><a href="#" title="<?php _e('Close', 'flickr-manager'); ?>" class="closeLink">x</a></div>');
		var list = jQuery('<ul><li><a href="#">Square</a></li><li><a href="#">Thumbnail</a></li></ul>');
		list.append(jQuery('<li><a href="#">Small</a></li><li><a href="#">Medium</a></li>'));
		wrapper.append(list);
		
		var listCSS = {
				"position" : "absolute",
				"width" : "100px",
				"border" : "1px solid #ccc",
				"background" : "#fff",
				"top" : e.pageY,
				"left" : e.pageX
		};
		wrapper.css(listCSS);
		if(parseFloat(wrapper.css('left').match(/\d+/)) + 100 > jQuery("body").width()) {
			wrapper.css('left', parseFloat(wrapper.css('left').match(/\d+/)) - 100);
		}
		jQuery("body").append(wrapper);
		
		jQuery('div.sizePicker>a.closeLink').click(function() {
			jQuery("div.sizePicker,img.loadingImage").remove().end();
			return false;
		});
		
		jQuery('div.sizePicker>ul>li>a').click(function() {
			
			var id = jQuery("select[@name='wfm-photoset']").val();
			var size = jQuery(this).html().toLowerCase();
			
			var lightbox = "false";
			if(jQuery("#wfm-lightbox").is(":checked")) lightbox = "true";
			
			var setHTML = '[flickrset id="' + id + '" thumbnail="' + size + '" overlay="' + lightbox + '" size="' + jQuery("select[@name='wfm-lbsize']").val() + '"]';
			sendToEditor(setHTML);
			jQuery("div.sizePicker,img.loadingImage").remove().end();
			
			return false;
		});
		
		return false;
	});
	
	jQuery("#wfm-photoset").change(function() {
		mediaRequest();
		if(jQuery(this).val() !== "") jQuery("a#wfm-entire-set").show();
		else jQuery("a#wfm-entire-set").hide();
	});
	
	jQuery("#wfm-filter").keypress(function(e) {
		var evt = (e) ? e : window.event;
		var type = evt.type;
		var pK = e ? e.which : window.event.keyCode;
		if (pK == 13) {
			mediaRequest();
			return false;
		}
	});
	
	jQuery("#wfm-lightbox").change(function() {
		if(jQuery(this).is(':checked')) jQuery("#wfm-overlay>div.settings").show();
		else jQuery("#wfm-overlay>div.settings").hide();
	});
	
	if(jQuery("#wfm-lightbox").is(':checked')) jQuery("#wfm-overlay>div.settings").show();
	else jQuery("#wfm-overlay>div.settings").hide();
	
	prepareNavigation();
	prepareImages();
	
});


var prepareNavigation = function() {
	
	var newNav = jQuery("#wfm-navigation").html();
	if(jQuery("#wfm-navigation:first").children().filter(':first').attr("id") == "wfm-navigation") {
		newNav = jQuery("#wfm-navigation:first").children().filter(':first').html();
	}
	jQuery("#wfm-dashboard").children().filter("#wfm-navigation:first").html(newNav);
	
	newNav = jQuery("#wfm-browse-content").html();
	if(jQuery("#wfm-browse-content:first").children().filter(':first').attr("id") == "wfm-browse-content") {
		var newNav = jQuery("#wfm-browse-content:first").children().filter(':first').html();
	}
	jQuery("#flickr-form").children().filter("#wfm-browse-content:first").html(newNav);
	
	jQuery("#wfm-filter-submit").click(function() {
		mediaRequest();
		return false;
	});

	jQuery("#wfm-navigation>a").click(function() {
		var uri = jQuery(this).attr("href").split("?");
		mediaRequest(uri[uri.length-1]);
		return false;
	});
	
};

function isDefined(variable) {
    return (typeof(variable) == "undefined") ? false : true;
}

var flickr_api_key = "0d3960999475788aee64408b64563028";
var flickr_secret = "b1e94e2cb7e1ff41";
var photo_sizes;

var prepareImages = function() {
	jQuery("div.sizePicker,img.loadingImage").remove().end();
	jQuery("div.flickr-img>img").click(function(e) {
	
		var id = jQuery(this).parent().attr("id").split("-")[1];
		var token = jQuery("#wfm-auth_token").val();
		var title = jQuery(this).attr("alt");
		var sizes_url = "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key="+ flickr_api_key + "&auth_token="+ token +"&photo_id="+ id +"&format=json";
		sizes_url = sizes_url + "&jsoncallback=selectSize&api_sig=" + jQuery.md5(flickr_secret + "api_key" + flickr_api_key + "auth_token"+ token +"formatjsonjsoncallbackselectSizemethodflickr.photos.getSizes" + "photo_id" + id);
		
		var owner = jQuery("#owner-" + id).attr("value");
		var longdesc = ' ';
		var fsize = jQuery("select[@name='wfm-lbsize']").val();
		if(typeof(jQuery(this).attr("longdesc")) != "undefined") {
			longdesc = ' longdesc="' + jQuery(this).attr("longdesc") + '" ';
		}
		var license = jQuery("#license-" + id);
		var wrapBefore = decodeURIComponent(jQuery("#wfm-insert-before").attr("value")).replace(/\\([\\'"])/g, '$1');
		var wrapAfter = decodeURIComponent(jQuery("#wfm-insert-after").attr("value")).replace(/\\([\\'"])/g, '$1');
		
		var target = ' ';
		if(jQuery("#wfm-blank").val() == "true") target = ' target="_blank" ';
		
		jQuery("div.sizePicker,img.loadingImage").remove().end();
		
		var wrapper = jQuery('<div class="sizePicker"><a href="#" title="<?php _e('Close', 'flickr-manager'); ?>" class="closeLink">x</a></div>');
		var list = jQuery('<ul></ul>');
		wrapper.append(list);
		
		var listCSS = {
				"position" : "absolute",
				"float" : "left",
				"border" : "1px solid #ccc",
				"background" : "#fff",
				"top" : e.pageY,
				"left" : e.pageX
		};
		
		var loadingImage = jQuery('<img alt="<?php _e('Loading...', 'flickr-manager'); ?>" class="loadingImage" />');
		loadingImage.attr("src", jQuery("#wfm-ajax-url").attr("value") + "/images/loading.gif");
		loadingImage.css(listCSS);
		loadingImage.css("width", "auto");
		jQuery("body").append(loadingImage);
		
		var furl = jQuery("#wfm-ajax-url").attr("value") + '/geturl.php?url=' + escape(sizes_url);
		jQuery.get( furl, function(data){
			eval(data);
		});
		
		function selectSize(data) {
			if(data.stat == "ok") {
				photo_sizes = data.sizes.size;
				
				jQuery.each(photo_sizes, function(i,size){
					var li = jQuery("<li/>");
					var link = jQuery("<a></a>");
					link.html(size.label + ' (' + size.width + 'x' + size.height + ')');
					link.attr("href", size.source);
					link.attr("title", size.label);
					
					link.click(function() {
						
						jQuery.each(photo_sizes, function(i,size){
							if(size.label == link.attr("title")) {
								var rel = ' rel="flickr-mgr" ';
								if(jQuery("#wfm-insert-set").is(":checked")) {
									rel = ' rel="flickr-mgr[' + jQuery("#wfm-set-name").val() + ']" ';
								}
								
								var imgHTML = "";
								if(jQuery("#wfm-lightbox").is(":checked")) {
									imgHTML = '<a href="http://www.flickr.com/photos/' + owner.split("|")[0] + "/" + id + '/" title="' + title + '"' + target + 'class="flickr-image"' + rel + '>';
									imgHTML = imgHTML + '<img src="' + link.attr('href') + '" alt="' + title + '" class="flickr-' + fsize + '" ' + longdesc + ' /></a>';
								} else {
									imgHTML = '<a href="http://www.flickr.com/photos/' + owner.split("|")[0] + "/" + id + '/" title="' + title + '"' + target + 'class="flickr-image">';
									imgHTML = imgHTML + '<img src="' + link.attr('href') + '" alt="' + title + '" /></a>';
								}
								
								if(license.attr("href")) {
									imgHTML = imgHTML + "<br /><small><a href='" + license.attr("href") + "' title='" + license.attr("title") + "' rel='license' " + target + ">" + license.html() + "</a> by <a href='http://www.flickr.com/people/"+owner.split("|")[0]+"/'"+ target +">"+owner.split("|")[1]+"</a></small>";
								}
								
								if(isDefined(wrapBefore) && wrapBefore !== 'undefined') {
									imgHTML = wrapBefore + imgHTML;
								}
								if(isDefined(wrapAfter) && wrapAfter !== 'undefined') {
									imgHTML = imgHTML + wrapAfter;
								}
								
								sendToEditor(imgHTML);
								
								jQuery("div.sizePicker").remove().end();
								
							}
						});
						return false;
					});
					
					jQuery("img.loadingImage").remove().end();
					list.append(li.append(link));
				});
				
				jQuery("body").append(wrapper);
				wrapper.css(listCSS);
				var minWidth = 135;
				if(parseFloat(wrapper.css('left').match(/\d+/)) + minWidth > jQuery("body").width()) {
					wrapper.css('width', minWidth);
					wrapper.css('left', parseFloat(wrapper.css('left').match(/\d+/)) - minWidth);
				} else if(parseFloat(wrapper.css('left').match(/\d+/)) + wrapper.width() > jQuery("body").width()) {
					wrapper.css('left', parseFloat(wrapper.css('left').match(/\d+/)) - wrapper.width());
				}
		
				var maxWidth = 150;
				if(parseFloat(wrapper.css('left').match(/\d+/)) > maxWidth) {
					wrapper.css('width', maxWidth);
				}
				
				jQuery('div.sizePicker>a.closeLink').click(function() {
					jQuery("div.sizePicker,img.loadingImage").remove().end();
				});
			} else {
				jQuery("div.sizePicker,img.loadingImage").remove().end();
			}
		};
		
	});
	
};

function mediaRequest(params) {
	url = jQuery("#flickr-form").attr('action');
	
	if(params) url = url + '&' + params;
	
	url = url + "&wfm-filter=" + jQuery("#wfm-filter").val();
	
	if(jQuery("select#wfm-photoset").val()) {
		url = url + "&wfm-photoset=" + jQuery("select#wfm-photoset").val();
	}
	
	jQuery("div.sizePicker,img.loadingImage").remove().end();
	
	var saveHeight = jQuery('#wfm-browse-content').height() + 'px';
	jQuery('#wfm-browse-content').css('min-height', saveHeight);
	jQuery('#wfm-browse-content').css('height', saveHeight);
	
	var loadingImage = jQuery("#wfm-ajax-url").attr("value") + "/images/loading.gif";
	jQuery("#wfm-browse-content").html(jQuery('<img src="' + loadingImage + '" alt="<?php _e('Loading...', 'flickr-manager'); ?>" />'));
	
	jQuery.get( url, function(data){
		jQuery('#wfm-browse-content').html(jQuery('<div>'+data+'</div>').find('#wfm-browse-content').html());
		jQuery('#wfm-navigation').html(jQuery('<div>'+data+'</div>').find('#wfm-navigation').html());
		
		prepareNavigation();
		prepareImages();
	});
}

var cancelAction = false;

function insertUpload() {
	if(!cancelAction) return true;
	
	var token = jQuery("#wfm-auth_token").val();
	var id = jQuery("input[@name='photo_id']").val();
	var wrapBefore = decodeURIComponent(jQuery("#wfm-insert-before").attr("value")).replace(/\\([\\'"])/g, '$1');
	var wrapAfter = decodeURIComponent(jQuery("#wfm-insert-after").attr("value")).replace(/\\([\\'"])/g, '$1');
	var target = ' ';
	if(jQuery("#wfm-blank").val() == "true") target = ' target="_blank" ';
	var longdesc = '';
	var rel = ' rel="flickr-mgr" ';
	if(jQuery("#wfm-insert-set").is(":checked")) {
		rel = ' rel="flickr-mgr[' + jQuery("#wfm-set-name").val() + ']" ';
	}
	var classStr = ' class="';
	if(jQuery("#wfm-lightbox").is(":checked")) {
		classStr = classStr + 'flickr-' + jQuery("select[@name='wfm-lbsize']").val();
	} else {
		rel = '';
	}
	classStr = classStr + '" ';
	
	var size = jQuery("input[@name='flickr-size']:checked").val();
	if(jQuery("select[@name='wfm-lbsize']").val() == 'original')
		longdesc = ' longdesc="' + jQuery('#original-url').val() + '" ';
	
	var imgHTML = '<a href="' + jQuery('#flickr-link').val() + '" title="' + jQuery('#flickr-title').val() + '"' + target + 'class="flickr-image align'+jQuery("input[@name='flickr-align']:checked").val()+'"' + rel + '>';
	imgHTML = imgHTML + '<img src="' + jQuery('#'+size+'-url').val() + '" alt="' + jQuery('#flickr-title').val() + '"' + classStr + longdesc + ' /></a>';
	
	if(isDefined(wrapBefore) && wrapBefore !== 'undefined') {
		imgHTML = wrapBefore + imgHTML;
	}
	if(isDefined(wrapAfter) && wrapAfter !== 'undefined') {
		imgHTML = imgHTML + wrapAfter;
	}
	
	top.send_to_editor(imgHTML);
	
	return false;
}

function sendToEditor(html) {
	if(jQuery("#wfm-close").is(":checked")) {
		top.send_to_editor(html);
	} else {
		var win = window.opener ? window.opener : window.dialogArguments;
		if ( !win ) win = top;
		tinyMCE = win.tinyMCE;
		var edCanvas = win.document.getElementById('content');
		
		if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
			ed.focus();
			if (tinyMCE.isIE)
				ed.selection.moveToBookmark(tinyMCE.EditorManager.activeEditor.windowManager.bookmark);
	
			ed.execCommand('mceInsertContent', false, html);
		} else if ( typeof edInsertContent == 'function' ) {
			edInsertContent(edCanvas, html);
		} else {
			jQuery( edCanvas ).val( jQuery( edCanvas ).val() + html );
		}
	}
}
