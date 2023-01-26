<?php namespace functions;
/**
 * write in the console with desired color and format
 * @param string $message
 * @param string[] $append
 * @param int[] $color
 * @param int[]|null $background
 * @param string[] $options bold|underline|italic
 * @return void
 */
function write(string $message, array $append = [], array $color = [200, 0, 0], array $background = null, $options = array())
{
	$formatText = ansiFormat($color,$background,$options);
	$debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	$time = date("H:i:s");
	$reset = "\033[0m";
	if(isset($time)) $append []= $time;
	if(isset($debug['class'])) $append []= $debug['class'];
	if(isset($debug['file'])) $append []= "{$debug['file']}:{$debug['line']}";

	$append = "[ ". join(" ][ ", $append) . " ]";
	$fat = ansiFormat([200, 255, 200], $background, [
		'bold'
	]);
	$arrow = ansiFormat([233,149,12], $background, [
		'bold'
	]);
	$preText = $fat . str_pad($append, 60, " ", STR_PAD_LEFT) . $reset;
	echo "{$preText} {$arrow}-->{$reset} {$formatText}{$message}{$reset}\r\n";
}