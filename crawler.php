<?php
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
function rgb2Ansi(int &$r = null, int &$g = null, int &$b = null)
{
	// Convert RGB values to 0-5 range
	$r = round($r ?? 0);
	$g = round($g ?? 0);
	$b = round($b ?? 0);
}
function rgbToAnsiFormat(array $color = null, array $background = null, $options = array()) {
	$format = "";
	$pre = chr(27);
	// Check if Color option is set
	if($color)
	{
		rgb2Ansi($color[0], $color[1], $color[2]);
		$format .= "{$pre}[38;2;{$color[0]};{$color[1]};{$color[2]}m";
	}
	// Check if background option is set
	if ($background) {
		rgb2Ansi($background[0], $background[1], $background[2]);
		$format .= "{$pre}[48;2;{$background[0]};{$background[1]};{$background[2]}m";
	}
	// Check if bold option is set
	if (in_array('bold', $options)) {
			$format .= "{$pre}[1m";
	}
	// Check if underline option is set
	if (in_array('underline', $options)) {
			$format .= "{$pre}[4m";
	}
	// Check if italic option is set
	if (in_array('italic', $options)) {
			$format .= "{$pre}[3m";
	}
	return $format;
}
function write(string $message, array $append = [], array $color = [200, 0, 0], array $background = null, $options = array())
{
	$formatText = rgbToAnsiFormat($color,$background,$options);
	$debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	$time = date("H:i:s");
	$reset = "\033[0m";
	if(isset($time)) $append []= $time;
	if(isset($debug['class'])) $append []= $debug['class'];
	if(isset($debug['file'])) $append []= "{$debug['file']}:{$debug['line']}";

	array_unshift($append, Crawler::getCount());

	$append = "[ ". join(" ][ ", $append) . " ]";
	$fat = rgbToAnsiFormat([200, 255, 200], $background, [
		'bold'
	]);
	$arrow = rgbToAnsiFormat([233,149,12], $background, [
		'bold'
	]);
	$preText = $fat . str_pad($append, 60, " ", STR_PAD_LEFT) . $reset;
	echo "{$preText} {$arrow}-->{$reset} {$formatText}{$message}{$reset}\r\n";
}
class Crawler
{
	public static $baseURL;
	const BODY = 'BODY';
	const STATUS = 'STATUS';

	public $url = "";
	public $from = "";
	public $status = 0;
	public $header = [];
	public $body = "";
	public function __construct(string $url, string $from = null) {
		$this->url = $url;
		$this->from = $from;
		$this->crawl($url);
		$this->findLinks();
		$this->lookAlerts();
	}
	private function header2KeyValuePair($header)
	{
		$headers = [];
		foreach (explode("\n", $header) as $value) {
			$keyValue = explode(":", $value);
			if(!empty($keyValue[0])) $headers[$keyValue[0]] = $keyValue[1] ?? null;
		}
		return $headers;
	}
	private function crawl(string $url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		$response = curl_exec($curl);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$this->header = $this->header2KeyValuePair(substr($response, 0, $header_size));
		$this->body = substr($response, $header_size);
		$this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
	}
	private function lookAlerts()
	{
		foreach(self::$Cases as $case)
		{
			$regex = $case[0];
			$within = $case[1];
			$level = $case[2];
			switch($within)
			{
				case self::BODY:
					$this->checkBody($regex, $level);
					break;
				case self::STATUS:
					$this->checkStatus($regex, $level);
					break;
			}
		}
	}
	private function checkBody($regex, $level)
	{
		if(preg_match($regex, $this->body))
		{
			write("Found `$regex` in \"{$this->url}\"", [$level]);
		}
	}
	private function checkStatus($regex, $level)
	{
		if(preg_match($regex, $this->status))
		{
			$linked = "";
			if(isset($this->from)) $linked = "linked from \"{$this->from}\"";
			write("Found `$regex` status is \"{$this->status}\" in \"{$this->url}\" $linked", [$level]);
		}
	}
	private function findLinks()
	{
		preg_match_all('/href="(.*?)"/im', $this->body, $matches);
		$links = $matches[1];
		foreach ($links as $link) {
			//write("Lokking at link `$link`", [], [0, 255, 200]);
			$link = fixURL($link, $this->url);
			if(!$link) continue;

			self::addUrl($link, $this->url);
		}
	}
	private static $Cases = [];
	public static function addAlertCase(string $regex, string $withIn = self::BODY, string $level = 'Warning')
	{
		if (isset(self::$Cases[$withIn.$regex]))
		{
			
			$debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
			throw new ErrorException("[" . self::class . "] Tryed to add `$regex` to Cases, but does already exists!", 0, E_COMPILE_ERROR, $debug['file'], $debug['line']);
		}
		self::$Cases[$withIn.$regex] = [$regex, $withIn, $level];
		write("Added `$regex` with level {$level}", [], [0,166,125]);
	}
	private static $crawlUrl = [];
	private static $checked = [];
	public static function getCount()
	{
		return count(self::$crawlUrl);
	}
	public static function addUrl($url, $from = null)
	{
		if (!isset(self::$baseURL))
			self::$baseURL = $url;
		if(!isset(self::$checked[$url]))
		{
			self::$crawlUrl[] = [$url, $from];
			self::$checked[md5($url)] = false;
		}
	}
	private static function pop()
	{
		$url = array_shift(self::$crawlUrl);
		if (empty($url))
			return false;
		//write("Running \"$url\"", [], [0, 166, 125]);
		self::$checked[md5($url[0])] = new self($url[0], $url[1]);
		return true;
	}

	public static function start()
	{
		while (self::pop());
		write("Done!", [], [0,166,125]);
	}
}

Crawler::addAlertCase('/error:/im');                               //Error in page
Crawler::addAlertCase('/^([4-9]\d{2,})$/im', Crawler::STATUS);    //status above 400

// Start the crawl at a given URL
Crawler::addUrl("https://bogbasen.dk");
Crawler::start();