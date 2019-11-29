<?php  
global $fakeTranslations;
$fakeTranslations = [];
if(!function_exists("__"))
{
	function __($target)
	{
	
		try{
			$t = app('translator')->getFromJson($target);
			if(env("DEBUG_TRANSLATION", "1"))
			{
				if(env("TRANSLATE_CHECK"))
				{
					global $fakeTranslations;
					if(!in_array($target, $fakeTranslations))
						array_push($fakeTranslations, $target);				
				}
				return $t == $target ? $target.env('FALSE_TRASNLATION_CHAR', "") : $t;				
			}
			else return $t;
		}
		catch(\Exception $e)
		{
			jsLog($target, "Error Translation");
			jsLog($e->getMessage());			
			return $target;
		}
		
	}
}
if(!function_exists("__t"))
{
	function __t($str) 
	{			
		$look = "{$str}";
		$t = __($look);
		if(env("DEBUG_TRANSLATION", "1"))
		{
			
			if(env("TRANSLATE_CHECK"))
			{
				global $fakeTranslations;
				if(!in_array($str, $fakeTranslations))
					array_push($fakeTranslations, $str);				
			}
			return $t == $look ? $str.env('FALSE_TRASNLATION_CHAR', "") : $t;
		}
		else return $t;
	}
}
if(!function_exists("jsLog"))
{
	function jsLog($target, $label = "jsLogged")
	{
		if(!is_scalar($target))
		{
			$target = json_encode($target);
		}
		else 
		{
			$target = "\"" . $target . "\"";
		}
		$label = "\"" . $label . "\"";
		?>
		
		<script>
			console.log(<?php echo $label ?>, <?php echo $target ?>);
		</script>

		<?php
	}
}
if(!function_exists("translate_check"))
{
	function translate_check() {
		global $fakeTranslations;
		?>
		
		<script>
			var fakeTranslations = <?php echo json_encode($fakeTranslations) ?>;
			console.log(fakeTranslations);
		</script>
			
		<?php
		\App\Models\Text::checkStringsAndInsertTheNew($fakeTranslations);
	}
}