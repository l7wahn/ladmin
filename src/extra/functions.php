<?php  

if(!function_exists("__"))
{
	function __($str, $where = "app") 
	{	
		$look = "{$where}.{$str}";
		$t = trans($look);

		echo $t == $look ? $str.env('FALSE_TRASNLATION_CHAR', "") : $t;
	}
}

if(!function_exists("__t"))
{
	function __t($str, $where = "app") 
	{	
		$look = "{$where}.{$str}";
		$t = trans($look);

		if(env("DEBUG_TRANSLATION", "1"))
		{
			return $t == $look ? $str.env('FALSE_TRASNLATION_CHAR', "") : $t;
		}
	}
}