<?php
/**
 * Model genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace WahnStudios\Laraadmin\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Google\Cloud\Translate\TranslateClient;
use Illuminate\Support\Collection;
use Log;
class Language extends Model
{
    use SoftDeletes;
	
	protected $table = 'languages';
	
	protected $hidden = [
        
    ];

	protected $guarded = [];

	protected $dates = ['deleted_at'];

	public static function checkByIso($iso) 
	{
		$language = Language::where('iso', $iso)->first();

        if($language == null)
        {
            $language = new Language();
            $language->iso = $iso;
            $language->name = "Requested [".$iso."]";
            $language->save();
		}
		
		return $language;
	}

	public function translations()
	{
		return $this->hasMany(Translation::class);
	}

	public function translatedArray() 
	{
		$ids = [];
		$o = [];
		foreach($this->translations()->with("sourceText")->get() as $translation)
		{
			$o[$translation->sourceText->text] = str_replace("|","****", $translation->text);
		}

		return $o;
	}

	public function nonTranslatedSources() 
	{
		$ids = [];
		foreach($this->translations as $translation)
		{
			array_push($ids, $translation->text_id);
		}
		
		return Text::whereNotIn('id', $ids)->get();
	}

	protected function nonTranslatedTexts() 
	{
		$sources = $this->nonTranslatedSources();
		$texts = [];
		foreach($sources as $text)
		{
			array_push($texts, $text->text);
		}

		return (object)[
			"sources" => $sources,
			"texts" => $texts
		];
	}

	public function googleTranslate()
	{
		$projectId = 'correntoso';
		
		
        # Instantiates a client
        $translate = new TranslateClient([
            'projectId' => $projectId
		]);
		$nonTranslated = $this->nonTranslatedTexts();
		
		if(count($nonTranslated->texts) == 0)
		{
			return;
		}

        # The text to translate
		$text = implode("<span class=\"notranslate\">|</span>", $nonTranslated->texts);
		
		jsLog($text);

        # The target language
        $target = $this->iso;

        # Translates some text into Russian
        $translation = $translate->translate($text, [
            'target' => $target
		]);

		
		$googleTranslations = explode("|", strip_tags(html_entity_decode($translation["text"])));	
		Log::debug(json_encode($googleTranslations));
		$translations = [];
		$i = 0;
		foreach($nonTranslated->sources as $source)
		{
			if(!empty($googleTranslations[$i]))
			$translations[$source->id] = $googleTranslations[$i++];
		}


		self::saveTranslations($translations, $this->id);
		
	}

	public static function saveTranslations($translations, $id)
	{
		$operationResult = true;
		try 
		{
			$tRows = [];
			foreach($translations as $source_id => $text)
			{	
				
				array_push($tRows, [
					'language_id' => $id,
					'text_id' => $source_id,
					"text" => $text,
					"key" => $id."_".$source_id
				]);
			}

			$allResults = $tRows;
			
			$rankings = [];
			foreach ($allResults as $result) {
				$rankings[] = implode(', ', [$result['text_id'], $result['language_id'], '"' . $result['text'] . '"', '"' . $result['key'] . '"']);
			}
			
			$rankings = Collection::make($rankings);
			
			$rankings->chunk(500)->each(function($ch) use($operationResult) {
				$rankingString = '';
				foreach ($ch as $ranking) {
					$rankingString .= '(' . $ranking . '), ';
				}
			
				$rankingString = rtrim($rankingString, ", ");
			
				try {
					\DB::insert("INSERT INTO translations (`text_id`, `language_id`, `text`, `key`) VALUES $rankingString ON DUPLICATE KEY UPDATE `text`=VALUES(`text`)");
				} catch (\Exception $e) {
					throw $e;
					$operationResult = false;
				}
			});

			return $operationResult;
		}
		catch(\Exception $e)
		{
			throw $e;
			return false;
		}
	}

	
	public function saveLangForLaravel() 
	{
		$path = \App::langPath();
		
		$contents = json_encode($this->translatedArray(), JSON_PRETTY_PRINT);

		file_put_contents($path."/".$this->iso.".json", $contents);		
	}
}
