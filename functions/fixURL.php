<?php namespace functions;
/**
 * fix url based on $_fromUrl
 * @param string $_url
 * @param string $_fromUrl
 * @param bool $debug
 * @return false|string if not a link to the host returns false
 */
function fixURL(string $_url, string $_fromUrl, bool $debug = false)
{
	if($debug) echo "\r\n\r\n-----\r\n\r\n";
	if($debug) var_dump($_url, $_fromUrl);
	if($debug) echo "\r\n-----\r\n\r\n";
	$url = parse_url($_url);
	if($debug) var_dump($_url, $url);

	if(isset($url['scheme']) && strpos($url['scheme'], "http") === false)
		return false;
	
	$fromUrl = parse_url($_fromUrl);
	if($debug) var_dump($_fromUrl, $fromUrl);

	if(isset($url['host']) && $fromUrl['host'] != $url['host'])
		return false;
	
	$host = "{$fromUrl['scheme']}://{$fromUrl['host']}";
	if (isset($url['path'])) {
		if (strpos($url['path'], "/") !== 0)
			$path = $fromUrl['path'] ?? '/' . $url['path'];
		else
			$path = $url['path'];
	}
	
	parse_str($url['query'] ?? '', $query);
	if(!empty($query))
	{
		$query = "?" . http_build_query($query);
		if(!isset($url['path'])) $path = $fromUrl['path'];
	} else $query = "";
	if (empty($path))
		$path = "/";
	$fragment = "";
	if (isset($url['fragment']))
		$fragment = "#{$url['fragment']}";
	$url = $host . preg_replace("/\/+/im", "/", "/{$path}{$query}{$fragment}");
	if($debug) var_dump($url);
	return $host . preg_replace("/\/+/im", "/", "/{$path}{$query}{$fragment}");
}