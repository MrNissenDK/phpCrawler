<?php
namespace classes;

/**
 * Propely should start make comments to all the functions in the class 🤔
 */

use Error;
use ErrorException;
use function functions\write;
use function functions\fixURL;
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
			write("Found `$regex` in \"{$this->url}\"", [self::getCount(), $level]);
		}
	}
	private function checkStatus($regex, $level)
	{
		if(preg_match($regex, $this->status))
		{
			$linked = "";
			if(isset($this->from)) $linked = "linked from \"{$this->from}\"";
			write("Found `$regex` status is \"{$this->status}\" in \"{$this->url}\" $linked", [self::getCount(), $level]);
		}
	}
	private function findLinks()
	{
		preg_match_all('/href="(.*?)"/im', $this->body, $matches);
		$links = $matches[1];
		foreach ($links as $link) {
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
		$hash = md5($url);
		if (empty($url)) throw new Error("No url given");
		if (!isset(self::$baseURL))
			self::$baseURL = $url;
		if(!isset(self::$checked[$hash]))
		{
			self::$crawlUrl[] = [$url, $from];
			self::$checked[$hash] = false;
		}
	}
	private static function pop()
	{
		$url = array_shift(self::$crawlUrl);
		if (empty($url))
			return false;
		self::$checked[md5($url[0])] = new self($url[0], $url[1]);
		return true;
	}

	public static function start()
	{
		write("Crawling on " . self::$baseURL, [], [0, 166, 125]);
		$last = 0;
		while (self::pop())
		{
			$linksNew = $last - self::getCount();
			if($last-- <= 0 || $linksNew >= 1000) 
			{
				write("Total links in list: " . self::getCount());
				$last = self::getCount();
			}
		}
		write("Done!", [], [0,166,125]);
	}
}