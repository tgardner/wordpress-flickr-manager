
jQuery(document).ready(function() {
	
	insertTabs();
	
	jQuery("a#wfm-entire-set").hide();
	
	jQuery("#flickr-form").submit(function() { return false; });
	
	jQuery("#wfm-scope-block>label>input").change(function() {
		flickrRequest();
		
		if(jQuery("input[@name='wfm-scope']:checked").val() == "Personal") jQuery("#wfm-imageset").show();
		else jQuery("#wfm-imageset").hide();
	});
	
	jQuery("#wfm-insert-set").change(function() {
		if (jQuery('#wfm-insert-set').is(':checked')) {
			jQuery("#wfm-set-name").focus();
		}
	});
	
	jQuery("#wfm-entire-set").click(function() {
		
		var size = jQuery("select[@name='wfm-size']").val();
		var id = jQuery("select[@name='wfm-photoset']").val();
		var lightbox = "";
		
		if(jQuery("#wfm-lightbox").is(":checked")) {
			lightbox = "true";
		} else {
			lightbox = "false";
		}
		
		var setHTML = "[imgset:" + id + "," + size + "," + lightbox + "]";
		
		if(jQuery("#wfm-close").is(":checked")) {
			top.send_to_editor(setHTML);
		} else {
			var win = window.opener ? window.opener : window.dialogArguments;
			if ( !win ) win = top;
			tinyMCE = win.tinyMCE;
			if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
				tinyMCE.selectedInstance.getWin().focus();
				tinyMCE.execCommand('mceInsertContent', false, setHTML);
			} else if (win.edInsertContent) win.edInsertContent(win.edCanvas, setHTML);
		}
		
		return false;
	});
	
	jQuery("#wfm-photoset").change(function() {
		flickrRequest();
		if(jQuery(this).val() !== "") jQuery("a#wfm-entire-set").show();
		else jQuery("a#wfm-entire-set").hide();
	});
	
	jQuery("#wfm-filter").keypress(function(e) {
		var evt = (e) ? e : window.event;
		var type = evt.type;
		var pK = e ? e.which : window.event.keyCode;
		if (pK == 13) {
			flickrRequest();
			return false;
		}
	});
	
	jQuery("select[@name='wfm-size']").change(function () {
		flickrRequest('&wfm-page=' + jQuery("#wfm-page").attr("value"));
	});
	
	prepareNavigation();
	prepareImages();
	
	jQuery("#wfm-upload-link").click(function() {
		jQuery("div.sizePicker,img.loadingImage").remove().end();
		jQuery("#sidemenu>li>a").removeClass("current");
		var url = jQuery(this).addClass("current").attr("href");
		var loadingImage = jQuery("#wfm-ajax-url").attr("value") + "/images/loading.gif";
		
		jQuery("#flickr-form").html(jQuery('<img src="' + loadingImage + '" alt="loading..." />'));
		jQuery("#flickr-form").load(url);
		
		return false;
	});
	
});


/*
 * INSERTS CODE INTO MEDIA TAB MENU SIMILAR TO:
 *		<li id="tab-flickr-upload">
 *			<a href="/wp-content/plugins/wordpress-flickr-manager/flickr-ajax.php?faction=media-upload" id="wfm-upload-link">
 *				Flickr Upload
 *			</a>
 *		</li>
 */
function insertTabs() {
	var uploadTab = jQuery('<li id="tab-flickr-upload"></li>')
	
	var uploadLink = jQuery('<a id="wfm-upload-link">Flickr Upload</a>')
	uploadLink.attr("href", jQuery("#wfm-ajax-url").attr("value") + "/flickr-ajax.php?faction=media-upload");
	
	uploadTab.append(uploadLink);
	jQuery("#sidemenu").append(uploadTab);
};


var prepareNavigation = function() {
	
	var newNav = jQuery("#wfm-navigation").html();
	if(jQuery("#wfm-navigation:first").children().filter(':first').attr("id") == "wfm-navigation") {
		var newNav = jQuery("#wfm-navigation:first").children().filter(':first').html();
	}
	jQuery("#wfm-dashboard").children().filter("#wfm-navigation:first").html(newNav);
	
	newNav = jQuery("#wfm-browse-content").html();
	if(jQuery("#wfm-browse-content:first").children().filter(':first').attr("id") == "wfm-browse-content") {
		var newNav = jQuery("#wfm-browse-content:first").children().filter(':first').html();
	}
	jQuery("#flickr-form").children().filter("#wfm-browse-content:first").html(newNav);
	
	jQuery("#wfm-filter-submit").click(function() {
		flickrRequest();
	});

	jQuery("#wfm-navigation>a").click(function() {
		var uri = jQuery(this).attr("href").split("?");
		
		flickrRequest(uri[uri.length-1]);
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
		if(isDefined(jQuery(this).attr("longdesc"))) {
			longdesc = ' longdesc="' + jQuery(this).attr("longdesc") + '" ';
		}
		var license = jQuery("#license-" + id);
		var wrapBefore = decodeURIComponent(jQuery("#wfm-insert-before").attr("value"));
		var wrapAfter = decodeURIComponent(jQuery("#wfm-insert-after").attr("value"));
		
		var target = ' ';
		if(jQuery("#wfm-blank").val() == "true") target = ' target="_blank" ';
		
		jQuery("div.sizePicker,img.loadingImage").remove().end();
		
		var wrapper = jQuery('<div class="sizePicker"><a href="#" title="Close" class="closeLink">x</a></div>');
		var list = jQuery('<ul></ul>');
		wrapper.append(list);
		
		var listCSS = {
				"position" : "absolute",
				"width" : "100px",
				"border" : "1px solid #ccc",
				"background" : "#fff",
				"top" : e.pageY,
				"left" : e.pageX
		};
		
		var loadingImage = jQuery('<img alt="Loading..." class="loadingImage" />');
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
					link.html(size.label);
					link.attr("href", size.source);
					
					link.click(function() {
						jQuery.each(photo_sizes, function(i,size){
							if(size.label == link.html()) {
								
								var imgHTML = "";
								if(jQuery("#wfm-lightbox").is(":checked")) {
									imgHTML = '<a href="http://www.flickr.com/photos/' + owner.split("|")[0] + "/" + id + '/" title="' + title + '"' + target + 'class="flickr-image">';
									imgHTML = imgHTML + '<img src="' + link.attr("href") + '" alt="' + title + '" class="' + fsize + '" ' + longdesc + ' /></a>';
								} else {
									imgHTML = '<a href="http://www.flickr.com/photos/' + owner.split("|")[0] + "/" + id + '/" title="' + title + '"' + target + 'class="flickr-image">';
									imgHTML = imgHTML + '<img src="' + link.attr("href") + '" alt="' + title + '" /></a>';
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
								
								if(jQuery("#wfm-close").is(":checked")) {
									top.send_to_editor(imgHTML);
								} else {
									var win = window.opener ? window.opener : window.dialogArguments;
									if ( !win ) win = top;
									tinyMCE = win.tinyMCE;
									if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
										tinyMCE.selectedInstance.getWin().focus();
										tinyMCE.execCommand('mceInsertContent', false, imgHTML);
									} else if (win.edInsertContent) win.edInsertContent(win.edCanvas, imgHTML);
								}
								
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
				
				jQuery('div.sizePicker>a.closeLink').click(function() {
					jQuery("div.sizePicker,img.loadingImage").remove().end();
				});
			} else {
				jQuery("div.sizePicker,img.loadingImage").remove().end();
			}
		};
		
	});
	
};

function flickrRequest(params) {
	var url = appendParameters(jQuery("#wfm-ajax-url").attr("value") + "/flickr-ajax.php?faction=media-browse");
	
	if(params) url = url + params;
	
	jQuery("div.sizePicker,img.loadingImage").remove().end();
	
	var loadingImage = jQuery("#wfm-ajax-url").attr("value") + "/images/loading.gif";
	
	jQuery("#wfm-browse-content").html(jQuery('<img src="' + loadingImage + '" alt="loading..." />'));
	
	jQuery.get( url, function(data){
		jQuery("#wfm-navigation").html(jQuery('<div>'+data+'</div>').find("#wfm-navigation").html());
		prepareNavigation();
		
		jQuery("#wfm-browse-content").html(jQuery('<div>'+data+'</div>').find("#wfm-browse-content").html());
		prepareImages();
	});
	
}

function appendParameters(url) {
	url = url + "&wfm-scope=" + jQuery("input[@name='wfm-scope']:checked").val();
	url = url + "&wfm-filter=" + jQuery("input[@name='wfm-filter']").val();
	url = url + "&wfm-photoset=" + jQuery("select[@name='wfm-photoset']").val();
	
	return url;
}


/**
 * jQuery MD5 hash algorithm function
 * 
 * 	<code>
 * 		Calculate the md5 hash of a String 
 * 		String $.md5 ( String str )
 * 	</code>
 * 
 * Calculates the MD5 hash of str using the » RSA Data Security, Inc. MD5 Message-Digest Algorithm, and returns that hash. 
 * MD5 (Message-Digest algorithm 5) is a widely-used cryptographic hash function with a 128-bit hash value. MD5 has been employed in a wide variety of security applications, and is also commonly used to check the integrity of data. The generated hash is also non-reversable. Data cannot be retrieved from the message digest, the digest uniquely identifies the data.
 * MD5 was developed by Professor Ronald L. Rivest in 1994. Its 128 bit (16 byte) message digest makes it a faster implementation than SHA-1.
 * This script is used to process a variable length message into a fixed-length output of 128 bits using the MD5 algorithm. It is fully compatible with UTF-8 encoding. It is very useful when u want to transfer encrypted passwords over the internet. If you plan using UTF-8 encoding in your project don't forget to set the page encoding to UTF-8 (Content-Type meta tag). 
 * This function orginally get from the WebToolkit and rewrite for using as the jQuery plugin.
 * 
 * Example
 * 	Code
 * 		<code>
 * 			$.md5("I'm Persian."); 
 * 		</code>
 * 	Result
 * 		<code>
 * 			"b8c901d0f02223f9761016cfff9d68df"
 * 		</code>
 * 
 * @alias Muhammad Hussein Fattahizadeh < muhammad [AT] semnanweb [DOT] com >
 * @link http://www.semnanweb.com/jquery-plugin/md5.html
 * @see http://www.webtoolkit.info/
 * @license http://www.gnu.org/licenses/gpl.html [GNU General Public License]
 * @param {jQuery} {md5:function(string))
 * @return string
 */

(function($){
	
	var rotateLeft = function(lValue, iShiftBits) {
		return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
	}
	
	var addUnsigned = function(lX, lY) {
		var lX4, lY4, lX8, lY8, lResult;
		lX8 = (lX & 0x80000000);
		lY8 = (lY & 0x80000000);
		lX4 = (lX & 0x40000000);
		lY4 = (lY & 0x40000000);
		lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
		if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
		if (lX4 | lY4) {
			if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
			else return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
		} else {
			return (lResult ^ lX8 ^ lY8);
		}
	}
	
	var F = function(x, y, z) {
		return (x & y) | ((~ x) & z);
	}
	
	var G = function(x, y, z) {
		return (x & z) | (y & (~ z));
	}
	
	var H = function(x, y, z) {
		return (x ^ y ^ z);
	}
	
	var I = function(x, y, z) {
		return (y ^ (x | (~ z)));
	}
	
	var FF = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var GG = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var HH = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var II = function(a, b, c, d, x, s, ac) {
		a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
		return addUnsigned(rotateLeft(a, s), b);
	};
	
	var convertToWordArray = function(string) {
		var lWordCount;
		var lMessageLength = string.length;
		var lNumberOfWordsTempOne = lMessageLength + 8;
		var lNumberOfWordsTempTwo = (lNumberOfWordsTempOne - (lNumberOfWordsTempOne % 64)) / 64;
		var lNumberOfWords = (lNumberOfWordsTempTwo + 1) * 16;
		var lWordArray = Array(lNumberOfWords - 1);
		var lBytePosition = 0;
		var lByteCount = 0;
		while (lByteCount < lMessageLength) {
			lWordCount = (lByteCount - (lByteCount % 4)) / 4;
			lBytePosition = (lByteCount % 4) * 8;
			lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
			lByteCount++;
		}
		lWordCount = (lByteCount - (lByteCount % 4)) / 4;
		lBytePosition = (lByteCount % 4) * 8;
		lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
		lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
		lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
		return lWordArray;
	};
	
	var wordToHex = function(lValue) {
		var WordToHexValue = "", WordToHexValueTemp = "", lByte, lCount;
		for (lCount = 0; lCount <= 3; lCount++) {
			lByte = (lValue >>> (lCount * 8)) & 255;
			WordToHexValueTemp = "0" + lByte.toString(16);
			WordToHexValue = WordToHexValue + WordToHexValueTemp.substr(WordToHexValueTemp.length - 2, 2);
		}
		return WordToHexValue;
	};
	
	var uTF8Encode = function(string) {
		string = string.replace(/\x0d\x0a/g, "\x0a");
		var output = "";
		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
				output += String.fromCharCode(c);
			} else if ((c > 127) && (c < 2048)) {
				output += String.fromCharCode((c >> 6) | 192);
				output += String.fromCharCode((c & 63) | 128);
			} else {
				output += String.fromCharCode((c >> 12) | 224);
				output += String.fromCharCode(((c >> 6) & 63) | 128);
				output += String.fromCharCode((c & 63) | 128);
			}
		}
		return output;
	};
	
	$.extend({
		md5: function(string) {
			var x = Array();
			var k, AA, BB, CC, DD, a, b, c, d;
			var S11=7, S12=12, S13=17, S14=22;
			var S21=5, S22=9 , S23=14, S24=20;
			var S31=4, S32=11, S33=16, S34=23;
			var S41=6, S42=10, S43=15, S44=21;
			string = uTF8Encode(string);
			x = convertToWordArray(string);
			a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
			for (k = 0; k < x.length; k += 16) {
				AA = a; BB = b; CC = c; DD = d;
				a = FF(a, b, c, d, x[k+0],  S11, 0xD76AA478);
				d = FF(d, a, b, c, x[k+1],  S12, 0xE8C7B756);
				c = FF(c, d, a, b, x[k+2],  S13, 0x242070DB);
				b = FF(b, c, d, a, x[k+3],  S14, 0xC1BDCEEE);
				a = FF(a, b, c, d, x[k+4],  S11, 0xF57C0FAF);
				d = FF(d, a, b, c, x[k+5],  S12, 0x4787C62A);
				c = FF(c, d, a, b, x[k+6],  S13, 0xA8304613);
				b = FF(b, c, d, a, x[k+7],  S14, 0xFD469501);
				a = FF(a, b, c, d, x[k+8],  S11, 0x698098D8);
				d = FF(d, a, b, c, x[k+9],  S12, 0x8B44F7AF);
				c = FF(c, d, a, b, x[k+10], S13, 0xFFFF5BB1);
				b = FF(b, c, d, a, x[k+11], S14, 0x895CD7BE);
				a = FF(a, b, c, d, x[k+12], S11, 0x6B901122);
				d = FF(d, a, b, c, x[k+13], S12, 0xFD987193);
				c = FF(c, d, a, b, x[k+14], S13, 0xA679438E);
				b = FF(b, c, d, a, x[k+15], S14, 0x49B40821);
				a = GG(a, b, c, d, x[k+1],  S21, 0xF61E2562);
				d = GG(d, a, b, c, x[k+6],  S22, 0xC040B340);
				c = GG(c, d, a, b, x[k+11], S23, 0x265E5A51);
				b = GG(b, c, d, a, x[k+0],  S24, 0xE9B6C7AA);
				a = GG(a, b, c, d, x[k+5],  S21, 0xD62F105D);
				d = GG(d, a, b, c, x[k+10], S22, 0x2441453);
				c = GG(c, d, a, b, x[k+15], S23, 0xD8A1E681);
				b = GG(b, c, d, a, x[k+4],  S24, 0xE7D3FBC8);
				a = GG(a, b, c, d, x[k+9],  S21, 0x21E1CDE6);
				d = GG(d, a, b, c, x[k+14], S22, 0xC33707D6);
				c = GG(c, d, a, b, x[k+3],  S23, 0xF4D50D87);
				b = GG(b, c, d, a, x[k+8],  S24, 0x455A14ED);
				a = GG(a, b, c, d, x[k+13], S21, 0xA9E3E905);
				d = GG(d, a, b, c, x[k+2],  S22, 0xFCEFA3F8);
				c = GG(c, d, a, b, x[k+7],  S23, 0x676F02D9);
				b = GG(b, c, d, a, x[k+12], S24, 0x8D2A4C8A);
				a = HH(a, b, c, d, x[k+5],  S31, 0xFFFA3942);
				d = HH(d, a, b, c, x[k+8],  S32, 0x8771F681);
				c = HH(c, d, a, b, x[k+11], S33, 0x6D9D6122);
				b = HH(b, c, d, a, x[k+14], S34, 0xFDE5380C);
				a = HH(a, b, c, d, x[k+1],  S31, 0xA4BEEA44);
				d = HH(d, a, b, c, x[k+4],  S32, 0x4BDECFA9);
				c = HH(c, d, a, b, x[k+7],  S33, 0xF6BB4B60);
				b = HH(b, c, d, a, x[k+10], S34, 0xBEBFBC70);
				a = HH(a, b, c, d, x[k+13], S31, 0x289B7EC6);
				d = HH(d, a, b, c, x[k+0],  S32, 0xEAA127FA);
				c = HH(c, d, a, b, x[k+3],  S33, 0xD4EF3085);
				b = HH(b, c, d, a, x[k+6],  S34, 0x4881D05);
				a = HH(a, b, c, d, x[k+9],  S31, 0xD9D4D039);
				d = HH(d, a, b, c, x[k+12], S32, 0xE6DB99E5);
				c = HH(c, d, a, b, x[k+15], S33, 0x1FA27CF8);
				b = HH(b, c, d, a, x[k+2],  S34, 0xC4AC5665);
				a = II(a, b, c, d, x[k+0],  S41, 0xF4292244);
				d = II(d, a, b, c, x[k+7],  S42, 0x432AFF97);
				c = II(c, d, a, b, x[k+14], S43, 0xAB9423A7);
				b = II(b, c, d, a, x[k+5],  S44, 0xFC93A039);
				a = II(a, b, c, d, x[k+12], S41, 0x655B59C3);
				d = II(d, a, b, c, x[k+3],  S42, 0x8F0CCC92);
				c = II(c, d, a, b, x[k+10], S43, 0xFFEFF47D);
				b = II(b, c, d, a, x[k+1],  S44, 0x85845DD1);
				a = II(a, b, c, d, x[k+8],  S41, 0x6FA87E4F);
				d = II(d, a, b, c, x[k+15], S42, 0xFE2CE6E0);
				c = II(c, d, a, b, x[k+6],  S43, 0xA3014314);
				b = II(b, c, d, a, x[k+13], S44, 0x4E0811A1);
				a = II(a, b, c, d, x[k+4],  S41, 0xF7537E82);
				d = II(d, a, b, c, x[k+11], S42, 0xBD3AF235);
				c = II(c, d, a, b, x[k+2],  S43, 0x2AD7D2BB);
				b = II(b, c, d, a, x[k+9],  S44, 0xEB86D391);
				a = addUnsigned(a, AA);
				b = addUnsigned(b, BB);
				c = addUnsigned(c, CC);
				d = addUnsigned(d, DD);
			}
			var tempValue = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);
			return tempValue.toLowerCase();
		}
	});
})(jQuery);
