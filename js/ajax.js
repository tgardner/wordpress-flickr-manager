
function parseResponse(request) {
	if (request.readyState == 4) {
		if (request.status == 200 || request.status == 304) {
			if (document.all) {
				document.all['flickr-ajax'].innerHTML = request.responseText;
			} else {
				var content = document.getElementById("flickr-ajax");
				content.innerHTML = request.responseText;
			}
		}
	}
}

function displayLoading(element) {
	var image = document.createElement("img");
	image.setAttribute("alt","loading...");
	image.setAttribute("src","/wp-content/plugins/flickr/images/loading.gif");
	image.className = "loading";
	element.innerHTML = "";
	element.appendChild(image);
}

function getHTTPObject() {
	var xhr = false;
	if (window.XMLHttpRequest) {
		xhr = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch(e) {
			try {
				xhr = new ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {
				xhr = false;
			}
		}
	}
	return xhr;
}

function sendData(url, data) {
	var request = getHTTPObject();
	if (request) {
		displayLoading(document.getElementById("flickr-ajax"));
		request.onreadystatechange = function() {
			parseResponse(request);
		};
		request.open("POST", url, true);
		request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
		request.send(data);
		return true;
	} else {
		return false;
	}
}

function getData(url) {
	var request = getHTTPObject();
	if (request) {
		displayLoading(document.getElementById("flickr-ajax"));
		request.onreadystatechange = function() {
			parseResponse(request);
		};
		request.open("GET", url, true);
		request.send(null);
		return true;
	} else {
		return false;
	}
}

function prepareLinks() {
	if (!document.getElementById || !document.getElementsByTagName) {
		return;
	}
	if (!document.getElementById("flickr-insert-widget")) {
		return;
	}
	var list = document.getElementById("flickr-insert-widget");
	var links = list.getElementsByTagName("a");
	for (var i=0; i < links.length; i++) {
		links[i].onclick = function() {
			var query = this.getAttribute("href").split("?")[1];
			var url = "/wp-content/plugins/wordpress-flickr-manager/flickr-ajax.php?"+query;
			return !getData(url);
		};
	}
}

function performFilter() {
	var filter = document.getElementById("flickr-filter").value;
	var size = document.getElementById("flickr-size");
	var query = "action=" + document.getElementById("flickr-action").value + "&photoSize=" + size.options[size.selectedIndex].value + "&filter=" + filter;
	var url = "/wp-content/plugins/wordpress-flickr-manager/flickr-ajax.php?"+query;
	return !getData(url);
}

function executeLink(link) {
	var query = link.getAttribute("href").split("?")[1];
	var url = "/wp-content/plugins/wordpress-flickr-manager/flickr-ajax.php?"+query;
	return !getData(url);
}

window.onload = prepareLinks;
