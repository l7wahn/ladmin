<?php

namespace DesarrollatuApp\NWCRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use DesarrollatuApp\NWCRM\Helpers\LAHelper;

class Menu extends Model
{
    protected $table = 'la_menus';
    
    protected $guarded = [
        
    ];

    public function children() 
    {
        return $this->hasMany(Menu::class, "parent");
    }
}
