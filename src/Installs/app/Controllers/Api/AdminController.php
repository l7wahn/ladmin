<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Traits\AssociatedToAccount;
use DesarrollatuApp\NWCRM\Models\Module;
use App\Models\Request as ServerRequest;
use DB;
class AdminController extends Controller
{
    public function api_get_menu() 
    {
        $modules =  Module::with("app_fields")->where("show_in_app", true)->get();    
        foreach ($modules as $module) {
            $module->amount = ("\\App\\Models\\".$module->model)::where("id", "<>" ,0)->count();
        }
        return $modules;
    }

    public function api_get_retrySuccess($_id)
    {
        $serverRequest = ServerRequest::find($_id); 
        $serverRequest->delete();  
        return response()->json(["success" => true]);  
    }
}
