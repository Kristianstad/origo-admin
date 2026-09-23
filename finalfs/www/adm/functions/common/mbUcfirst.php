<?php

	// Same as PHP's ucfirst(), but multibyte-safe (handles UTF-8 first characters
	// such as å/ä/ö), since ucfirst() only understands single-byte (ASCII) letters.
	// Falls back to plain ucfirst() if mbstring isn't loaded (e.g. local dev without it).
	function mbUcfirst($str)
	{
		if ($str === '')
		{
			return $str;
		}
		if (!function_exists('mb_strtoupper'))
		{
			return ucfirst($str);
		}
		return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($str, 1, null, 'UTF-8');
	}