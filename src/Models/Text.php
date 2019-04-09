<?php
/**
 * Model genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace WahnStudios\Laraadmin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Text extends Model
{
    use SoftDeletes;
	
	protected $table = 'texts';
	
	protected $hidden = [
        
    ];

	protected $guarded = [];

	protected $dates = ['deleted_at'];

	public static function checkStringsAndInsertTheNew(array $strings)
	{
		$serverTexts = self::whereIn('text', $strings)->get(['id', 'text']);
        
        $existingTexts = [];
        
        foreach($serverTexts as $text)        
        {
            $existingTexts[$text->id] = $text->text;    
        }

        $strings = array_diff($strings, $existingTexts);
		
		$rows = [];

		foreach($strings as $string) 
		{
			array_push($rows, [
				'text' => $string
			]);
		}

		if(!empty($rows))
			return self::insert($rows);	

		return true;
	}
}
