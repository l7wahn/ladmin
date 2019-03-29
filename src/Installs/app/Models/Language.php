<?php
/**
 * Model genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

	public function nonTranslatedSources() 
	{
		$ids = [];
		foreach($this->translations as $translation)
		{
			array_push($ids, $translation->text_id);
		}
		
		return Text::whereNotIn('id', $ids)->get();
	}
	
}
