<?php

<<<<<<< HEAD
namespace Dwij\Laraadmin\Models;
=======
namespace Dwij\LaradminModels;
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;
use Log;
use DB;
<<<<<<< HEAD
use Dwij\Laraadmin\Helpers\LAHelper;

=======
use Dwij\LaradminHelpers\LAHelper;

/**
 * Class LAConfigs
 * @package Dwij\LaradminModels
 *
 * Config Class looks after LaraAdmin configurations.
 * Check details on http://laraadmin.com/docs
 */
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d
class LAConfigs extends Model
{   
	protected $table = 'la_configs';
	
	protected $fillable = [
		"key", "value"
	];
	
	protected $hidden = [
		
	];

	// LAConfigs::getByKey('sitename');
	public static function getByKey($key) {
		$row = LAConfigs::where('key',$key)->first();
		if(isset($row->value)) {
			return $row->value;
		} else {
			return false;
		}
	}
	
	// LAConfigs::getAll();
	public static function getAll() {
		$configs = array();
		$configs_db = LAConfigs::all();
		foreach ($configs_db as $row) {
			$configs[$row->key] = $row->value;
		}
		return (object) $configs;
	}
}
