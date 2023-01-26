<?php namespace classes;
use DateTime;
/**
 * Autoload is loading classes automaticly
 * @property string $dir
 * @property string $file
 * @property \DateTime $time
 * @property bool $exists
 */
class Autoload
{
	/**
	 * All loaded files done by the Autoload class
	 * @var string[]
	 */
	private static $loaded = [];
	public function __construct(string $filepath = null) {
		$this->dir = dirname($filepath);
		$this->file = basename($filepath);
		$this->time = new DateTime();
		$this->exists = file_exists($filepath);
		if($this->exists) require_once($filepath);
	}
	/**
	 * Recive all loaded files done by the Autoload class
	 * @return Autoload[]
	 */
	public static function getLoaded()
	{
		return self::$loaded;
	}
	/**
	 * Load File
	 * @property string $filepath
	 * @return bool
	 */
	public static function loadFile(string $filepath)
	{
		$load = new self($filepath);
		self::$loaded[] = $load;
		return $load->exists;
	}
	/**
	 * Load class
	 * @param string $className
	 * @return void
	 */
	public static function load(string $className)
	{
		$path = ROOT . "/" . str_replace("\\", "/", $className) . ".php";
		self::loadFile($path);
	}
	/**
	 * Only the because there ar noway to load functions as you need them automatically that I know of 😒
	 * @param string $dir
	 * @return void
	 */
	static function loadFunctions($dir)
	{
		foreach(glob("$dir/*.php") as $functionFile)
		{
			self::loadFile($functionFile);
		}
	}
}
spl_autoload_register(function ($className) {
	Autoload::load($className);
});
Autoload::loadFunctions(ROOT . "/functions");