<?php

<<<<<<< HEAD
namespace Dwij\Laraadmin\Models;
=======
namespace Dwij\LaradminModels;
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d

use Illuminate\Database\Eloquent\Model;

class ModuleFieldTypes extends Model
{
    protected $table = 'module_field_types';
    
    protected $fillable = [
        "name"
    ];
    
    protected $hidden = [
        
    ];
    
    // ModuleFieldTypes::getFTypes()
    public static function getFTypes() {
        $fields = ModuleFieldTypes::all();
        $fields2 = array();
        foreach ($fields as $field) {
            $fields2[$field['name']] = $field['id'];
        }
        return $fields2;
    }
    
    // ModuleFieldTypes::getFTypes2()
    public static function getFTypes2() {
        $fields = ModuleFieldTypes::all();
        $fields2 = array();
        foreach ($fields as $field) {
            $fields2[$field['id']] = $field['name'];
        }
        return $fields2;
    }
}
