<?php 
namespace MultipleRows\Utilites;

class Str
{
	public static function explode(string $haysack, string $delimeter)
	{ 
		// Negative Lookbehind is used in matching this
		return array_map("stripslashes", preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $constraint));
	}
}