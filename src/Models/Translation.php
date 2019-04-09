<?php
/**
 * Model genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace WahnStudios\Laraadmin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    use SoftDeletes;
	
	protected $table = 'translations';
	
	protected $hidden = [
        
    ];

	protected $guarded = [];

	protected $dates = ['deleted_at'];

	public function sourceLanguage() 
	{
		return $this->belongsTo(Language::class);
	}

	public function sourceText() 
	{
		return $this->belongsTo(Text::class, 'text_id');
	}

	public static function getByIso($iso)
	{
		$language = Language::checkByIso($iso);
		return Translation::where('language_id', $language->id)->with('sourceText')->get();
	}
}
