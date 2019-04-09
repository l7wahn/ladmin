<?php
/**
 * Controller genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace WahnStudios\Laraadmin\Controllers;

use WahnStudios\Laraadmin\Controllers\Controller;
use Illuminate\Http\Request;
use WahnStudios\Laraadmin\Models\Language;
class LanguagesController extends Controller
{
	protected $module_name = "Languages";	
	protected $view_base = "la.languages";

	protected function beforeUpdate(Request $request, $id) 
	{
		$withTranslations = $request->has("translations");
		$savedTranslations = false;
		if($withTranslations)
		{ 
			$translations = $request->translations;
			$savedTranslations = Language::saveTranslations($translations, $id);				
		}
	}

	protected function updateAction(Request $request, $id)
	{
		
		if($request->has("translations"))
		{
			return redirect()->back();
		}

		return parent::updateAction($request, $id); 
	}

	protected function editAction($id, $instance)
	{
		$instance->googleTranslate();
		$instance->saveLangForLaravel();
		return parent::editAction($id, $instance);
		
	}

	
}
