<?php
$flickr_apikey = "0d3960999475788aee64408b64563028";
$flickr_secret = "b1e94e2cb7e1ff41";

function flickr_call($method, $params, $sign = false, $rsp_format = "php_serial") {
	
	if(!is_array($params) || !is_string($method) || !is_string($rsp_format) || !is_bool($sign)) return false;
	
	global $flickr_apikey;
	$call_includes = array( 'api_key'	=> $flickr_apikey, 
							'method'	=> $method,
							'format'	=> $rsp_format);
	
	$params = array_merge($call_includes, $params);
	
	if($sign) $params = array_merge($params, array('api_sig' => flickr_sig($params)));
	
	$url = "http://api.flickr.com/services/rest/?".flickr_encode($params);
	
	$session = curl_init($url);
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($session);
	curl_close($session);
	$rsp_obj = unserialize($response);
    return $rsp_obj;
    
	
}

function flickr_post($method, $params, $sign = false, $rsp_format = "php_serial") {
	
	if(!is_array($params) || !is_string($method) || !is_string($rsp_format) || !is_bool($sign)) return false;
	
	global $flickr_apikey;
	$call_includes = array( 'api_key'	=> $flickr_apikey, 
							'method'	=> $method,
							'format'	=> $rsp_format);
	
	$params = array_merge($call_includes, $params);
	
	if($sign) $params = array_merge($params, array('api_sig' => flickr_sig($params)));
	
	$url = "http://api.flickr.com/services/rest/";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT,200);
    
    set_time_limit(20 + 200 + 5);
    $result = curl_exec($ch);
    
    if (curl_errno($ch) == 0) {
    	curl_close($ch);
        return $result;
    }
    curl_close($ch);
	return false;
	
}

function flickr_upload($params) {
	
	if(!is_array($params) || !isset($params['photo'])) return false;
	
	$photo = $params['photo'];
	unset($params['photo']);
	
	global $flickr_apikey;
	$call_includes = array( 'api_key'	=> $flickr_apikey);
	
	$params = array_merge($call_includes, $params);
	$params = array_merge($params, array('photo' => $photo, 'api_sig' => flickr_sig($params)));
	
	$url = "http://api.flickr.com/services/upload/";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT,200);
    
    set_time_limit(20 + 200 + 5);
    $result = curl_exec($ch);
    
    if (curl_errno($ch) == 0) {
    	curl_close($ch);
        return $result;
    }
    curl_close($ch);
	return false;
}

function flickr_encode($params) {
	$encoded_params = array();

	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	
	return implode('&', $encoded_params);
}

function flickr_sig($params) {
	ksort($params);
	
	global $flickr_secret;
	$api_sig = $flickr_secret;
	
	foreach ($params as $k => $v){
		$api_sig .= $k . $v;
	}
	
	return md5($api_sig);
}

function flickr_auth_url($frob, $perms) {
	global $flickr_apikey;
	
	$params = array('api_key' => $flickr_apikey, 'perms' => $perms, 'frob' => $frob);
	$params = array_merge($params, array('api_sig' => flickr_sig($params)));
	
	$url = 'http://flickr.com/services/auth/?'.flickr_encode($params);
	return $url;
}

function flickr_photo_url($photo, $size) {
	$sizes = array('square' => '_s', 'thumbnail' => '_t', 'small' => '_m', 'medium' => '', 'large' => '_b', 'original' => '_o');
	
	if(($size = strtolower($size)) != 'original') {
		$url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}{$sizes[$size]}.jpg";
	} else {
		$url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['originalsecret']}{$sizes[$size]}.{$photo['originalformat']}";
	}
	return $url;
}

?>