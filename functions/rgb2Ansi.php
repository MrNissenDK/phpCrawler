<?php namespace functions;
/**
 * fix the rgb to ansi ready values
 * @param int|null $r
 * @param int|null $g
 * @param int|null $b
 * @return void
 */
function rgb2Ansi(int &$r = null, int &$g = null, int &$b = null)
{
	// Convert RGB values to 0-5 range
	$r = round($r ?? 0);
	$g = round($g ?? 0);
	$b = round($b ?? 0);
}