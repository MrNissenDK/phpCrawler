<?php
function fixURL(string $_url, string $_fromUrl)
{
	echo "\r\n\r\n-----\r\n\r\n";
	var_dump($_url, $_fromUrl);
	echo "\r\n-----\r\n\r\n";
	$url = parse_url($_url);
	var_dump($_url, $url);

	if(isset($url['scheme']) && strpos($url['scheme'], "http") === false)
		return 1;
	
	$fromUrl = parse_url($_fromUrl);
	var_dump($_fromUrl, $fromUrl);

	if(isset($url['host']) && $fromUrl['host'] != $url['host'])
		return 2;
	
	$host = "{$fromUrl['scheme']}://{$fromUrl['host']}";
	if (isset($url['path'])) {
		echo "path is set\r\n";
		if (strpos($url['path'], "/") !== 0)
			$path = $fromUrl['path'] . $url['path'];
		else
			$path = $url['path'];
	}
	
	parse_str($url['query'] ?? '', $query);
	if(isset($query))
	{
		$query = "?" . http_build_query($query);
		if(!isset($url['path'])) $path = $fromUrl['path'];
	} else $query = "";

	$fragment = "";
	if (isset($url['fragment']))
		$fragment = "#{$url['fragment']}";
	
	return $host . preg_replace("/\/+/im", "/", "/{$path}{$query}{$fragment}");
}
$baseUrl = "https://bogbasen.dk/";
var_dump( $baseUrl );
$newUrl = fixURL("//bogbasen.dk?sub#666", $baseUrl);
var_dump( $newUrl );
$newUrl2 = fixURL("/lag?111#555", $newUrl);
var_dump( $newUrl2 );
$newUrl3 = fixURL("?kage=555 & 111", $newUrl2);
var_dump( $newUrl3 );