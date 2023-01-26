<?php namespace functions;
/**
 * Make a string for color and format in a terminal suporting ansi
 * @param int[]|null $color
 * @param int[]|null $background
 * @param string[] $options bold|underline|italic
 * @return string 
 */
function ansiFormat(array $color = null, array $background = null, $options = array()) {
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