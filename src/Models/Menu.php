<?php

<<<<<<< HEAD
namespace Dwij\Laraadmin\Models;
=======
namespace Dwij\LaradminModels;
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
<<<<<<< HEAD
use Dwij\Laraadmin\Helpers\LAHelper;

=======
use Dwij\LaradminHelpers\LAHelper;

/**
 * Class Menu
 * @package Dwij\LaradminModels
 *
 * Menu Model which looks after Menus in Sidebar and Navbar
 */
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d
class Menu extends Model
{
    protected $table = 'la_menus';
    
    protected $guarded = [
        
    ];
}
