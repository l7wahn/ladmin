<?php
/**
 * Controller genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace App\Http\Controllers\LA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;
use DB;
use Validator;
use Datatables;
use Collective\Html\FormFacade as Form;
use DesarrollatuApp\NWCRM\Models\Module;
use DesarrollatuApp\NWCRM\Models\ModuleFields;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Collection;

class LanguagesController extends Controller
{
	public $show_action = true;
	public $view_col = 'name';
	public $listing_cols = ['id', 'name', 'iso'];
	
	public function __construct() {
		// Field Access of Listing Columns
		if(\DesarrollatuApp\NWCRM\Helpers\LAHelper::laravel_ver() >= 5.3) {
			$this->middleware(function ($request, $next) {
				$this->listing_cols = ModuleFields::listingColumnAccessScan('Languages', $this->listing_cols);
				return $next($request);
			});
		} else {
			$this->listing_cols = ModuleFields::listingColumnAccessScan('Languages', $this->listing_cols);
		}
	}

	public function google_translate($id)
	{
		$lang = Language::find($id);
		$lang->googleTranslate();
		$lang->saveLangForLaravel();
		return redirect()->back();
	}
	
	/**
	 * Display a listing of the Languages.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$module = Module::get('Languages');
		
		if(Module::hasAccess($module->id)) {
			return View('la.languages.index', [
				'show_actions' => $this->show_action,
				'listing_cols' => $this->listing_cols,
				'module' => $module
			]);
		} else {
            return redirect(config('laraadmin.adminRoute')."/");
        }
	}

	/**
	 * Show the form for creating a new language.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		
	}

	/**
	 * Store a newly created language in database.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		if(Module::hasAccess("Languages", "create")) {
		
			$rules = Module::validateRules("Languages", $request);
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();
			}
			
			$insert_id = Module::insert("Languages", $request);
			
			return redirect()->route(config('laraadmin.adminRoute') . '.languages.index');
			
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Display the specified language.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		if(Module::hasAccess("Languages", "view")) {
			
			$language = Language::find($id);
			if(isset($language->id)) {
				$module = Module::get('Languages');
				$module->row = $language;
				
				return view('la.languages.show', [
					'module' => $module,
					'view_col' => $this->view_col,
					'no_header' => true,
					'no_padding' => "no-padding"
				])->with('language', $language);
			} else {
				return view('errors.404', [
					'record_id' => $id,
					'record_name' => ucfirst("language"),
				]);
			}
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Show the form for editing the specified language.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		if(Module::hasAccess("Languages", "edit")) {			
			$language = Language::find($id);
			if(isset($language->id)) {	
				$module = Module::get('Languages');
				
				$module->row = $language;
				
				return view('la.languages.edit', [
					'module' => $module,
					'view_col' => $this->view_col,
				])->with('language', $language);
			} else {
				return view('errors.404', [
					'record_id' => $id,
					'record_name' => ucfirst("language"),
				]);
			}
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Update the specified language in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		

		if(Module::hasAccess("Languages", "edit")) {
			
			$withTranslations = $request->has("translations");
			$savedTranslations = false;
			if($withTranslations)
			{ 
				$translations = $request->translations;
				$savedTranslations = $this->saveTranslations($translations, $id);	
							
			}
			

			$rules = Module::validateRules("Languages", $request, true);
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();;
			}
			
			$insert_id = Module::updateRow("Languages", $request, $id);
			
			if($withTranslations) return redirect()->back();

			return redirect()->route(config('laraadmin.adminRoute') . '.languages.index');
			
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	public function saveTranslations($translations, $id)
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

			Language::find($id)->saveLangForLaravel();

			return $operationResult;
		}
		catch(\Exception $e)
		{
			throw $e;
			return false;
		}
	}

	/**
	 * Remove the specified language from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		if(Module::hasAccess("Languages", "delete")) {
			Language::find($id)->delete();
			
			// Redirecting to index() method
			return redirect()->route(config('laraadmin.adminRoute') . '.languages.index');
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}
	
	/**
	 * Datatable Ajax fetch
	 *
	 * @return
	 */
	public function dtajax()
	{
		$values = DB::table('languages')->select($this->listing_cols)->whereNull('deleted_at');
		$out = Datatables::of($values)->make();
		$data = $out->getData();

		$fields_popup = ModuleFields::getModuleFields('Languages');
		
		for($i=0; $i < count($data->data); $i++) {
			for ($j=0; $j < count($this->listing_cols); $j++) { 
				$col = $this->listing_cols[$j];
				if($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
					$data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
				}
				if($col == $this->view_col) {
					$data->data[$i][$j] = '<a href="'.url(config('laraadmin.adminRoute') . '/languages/'.$data->data[$i][0]).'/edit">'.$data->data[$i][$j].'</a>';
				}
			}
			
			if($this->show_action) {
				$output = '';
				if(Module::hasAccess("Languages", "edit")) {
					$output .= '<a href="'.url(config('laraadmin.adminRoute') . '/languages/'.$data->data[$i][0].'/edit').'" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
				}
				
				if(Module::hasAccess("Languages", "delete")) {
					$output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.languages.destroy', $data->data[$i][0]], 'method' => 'delete', 'style'=>'display:inline']);
					$output .= ' <button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i></button>';
					$output .= Form::close();
				}
				$data->data[$i][] = (string)$output;
			}
		}
		$out->setData($data);
		return $out;
	}
}
