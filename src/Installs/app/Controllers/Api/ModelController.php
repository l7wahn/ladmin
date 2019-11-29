<?php

/**
 * Controller genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Address;
use Auth;
use DB;
use Validator;
use Datatables;
use Collective\Html\FormFacade as Form;
use DesarrollatuApp\NWCRM\Models\Module;
use DesarrollatuApp\NWCRM\Models\ModuleFields;

use App\Models\ClientChildren;
use App\Models\Country;
use Illuminate\Support\Facades\Input;

class ModelController extends Controller
{
	public $show_action = true;
	public $view_col = 'children_id';
	public $listing_cols = ['id', 'children_id', 'client_id', 'relationship', 'relationship_other'];

	public function __construct()
	{ }

	/**
	 * Display a listing of the ClientChildrens.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index($model)
	{
		$module = Module::getByTable($model);

		if (true || Module::hasAccess($module->id)) {
			$all = ("\\App\\Models\\" . $module->model)::select("*");

			if (Input::has("where")) {
				$conditions = array_map(function ($condition) {
					return explode(",", trim($condition, " "));
				}, explode("|", Input::get("where")));

				$all->where($conditions);
			}


			if (Input::has("with")) {
				$all->with(explode("|", Input::get("with")));
			}
			$all = $all->get();

			

			$this->getRelationships($module, $all);

			if($module->category_field != null)
			{
				$all = $all->groupBy($module->category_field);
			}

			return $this->successResponse($all);
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}

	private function getRelationships($module, $all)
	{
		$fields = $module->app_fields;

		foreach ($fields as $f) {
			foreach ($all as $item) {

				$this->setPopup($item, $f);
			}
		}
	}

	private function setPopup($item, $f, $types = null)
	{
		if ($types != null && !in_array($f->field_type, $types)) return;

		$field = (object) $f;



		if ($field->field_type == 1) {
			$item->addressDescriptions = (object) [];
			$address = Address::find($item->{$f["colname"]});

			if ($address != null) {
				$item->addressDescriptions->{$f["colname"]} = $address->description;
			}
		} else {
			if ($field->field_type != 15 && starts_with($f["popup_vals"], "@")) {
				$item->{$f["colname"]} = ModuleFields::getFieldValue($field, $item->{$f["colname"]});
			}
		}
	}



	/*public function errorResponse($code = 500 , $message = "Successfully retrieved") 
    {
        return response()->json([
            "success" => false,
            "message" => $message
        ], $code);
    }
    
    public function successResponse($answer, $message = "Successfully retrieved") 
    {
        return response()->json([
            "success" => true,
            "data" => $answer,
            "message" => $message
        ]);
    }*/

	/**
	 * Show the form for creating a new clientchildren.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	public function import(Request $request, $modelName)
	{
		if (true || Module::hasAccess($modelName, "create")) {

			$validator = $this->validateRulesForCSV($modelName, $request);
			if ($validator->failed) {
				return response()->json($validator, 422);
			}
			$insert_id = Module::insert($modelName, $request);
			$newModel = ("\\App\\Models\\" . Module::getByTable($modelName)->model)::find($insert_id);;
			return $this->successResponse($newModel);
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}

	private function validateRulesForCSV($modelName, Request $request)
	{


		$module = Module::getByTable($modelName);
		$errors = [];
		$warnings = [];
		$addressDescriptions = [];
		foreach ($module->app_fields as $f) {
			$field = $f->toArray();
			if (!$request->has($field['colname'])) {
				continue;
			}

			switch ($field['field_type']) {
				case 1:
					if ($request->{$field['colname']} == null) break;

					$addresses = Address::search($request->{$field['colname']}, env("FALLBACK_COUNTRY_ISO"));

					if ($addresses == null) {
						$errors[$field['colname']] = [trans("We couldn't find the address")];
					} else if (count($addresses) == 1) {
						$toMerge = [];
						$toMerge[$field['colname']] = $addresses[0]->id;
						$request->merge($toMerge);
						$addressDescriptions[$field['colname']] = $addresses[0]->description;
					} else if (count($addresses) > 1) {
						$errors[$field['colname']] = [trans("We found too many options for this address")];
					}

					break;
				case 14:
					$country = Country::whereRaw("? LIKE CONCAT(area_code, '%')", $request->{$field['colname']})->first();
					if ($country == null) {
						$country = Country::where("iso", env("FALLBACK_COUNTRY_ISO"))->first();
						$toMerge = [];
						$toMerge[$field['colname']] = $country->area_code . $request->{$field['colname']};
						$request->merge($toMerge);

						$warnings[$field['colname']] = [trans("We selected the country for you, {$country->name} selected")];
					}
					break;
			}
		}

		$rules = $module->ownValidateRules();

		$validator = Validator::make($request->all(), $rules);
		
		
		if ($validator->fails() || !empty($errors)) {
			$validatorErrors = $validator->errors()->toArray();

			foreach ($errors as $column => $error) {
				if (!array_key_exists($column, $validatorErrors)) {
					$validatorErrors[$column] = [];
				}
				$validatorErrors[$column] += $error;
			}

			foreach ($module->app_fields as $f) {
				$field = $f->toArray();
				if (!$request->has($field['colname'])) {
					continue;
				}

				switch ($field['field_type']) {
					case 1:
						if(array_key_exists($field['colname'], $addressDescriptions))
						{
							$toMerge[$field['colname']] = $addressDescriptions[$field['colname']];
							$request->merge($toMerge);
						}
						break;					
				}
			}

			return (object) [
				"failed" => true,
				"errors" => $validatorErrors,
				"warnings" => $warnings,
				"model" => $request->all()
			];
		} else {
			return (object) [
				"failed" => false,
				"errors" => null
			];
		}
	}

	public function popupVals(Request $request = null) 
	{
		$data = [];
		foreach($request->names as $name)
		{
			$data[$name] = DB::table($name)->select(DB::raw("id, ".Module::getByTable($name)->view_col." as name"))->get();		
		}
		return $this->successResponse($data); 
	}

	/**
	 * Store a newly created clientchildren in database.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request, $modelName)
	{
		if (true || Module::hasAccess($modelName, "create")) {

			$rules = Module::validateRules($modelName, $request);

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
				return $validator->errors();
			}
			$insert_id = Module::insert($modelName, $request);
			$newModel = ("\\App\\Models\\" . Module::getByTable($modelName)->model)::find($insert_id);;
			return $this->successResponse($newModel);
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}

	/**
	 * Display the specified clientchildren.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		if (Module::hasAccess("ClientChildrens", "view")) {

			$clientchildren = ClientChildren::find($id);
			if (isset($clientchildren->id)) {
				$module = Module::get('ClientChildrens');
				$module->row = $clientchildren;

				return view('la.clientchildrens.show', [
					'module' => $module,
					'view_col' => $this->view_col,
					'no_header' => true,
					'no_padding' => "no-padding"
				])->with('clientchildren', $clientchildren);
			} else {
				return view('errors.404', [
					'record_id' => $id,
					'record_name' => ucfirst("clientchildren"),
				]);
			}
		} else {
			return redirect(config('laraadmin.adminRoute') . "/");
		}
	}

	/**
	 * Show the form for editing the specified clientchildren.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($model, $id)
	{
		$module = Module::getByTable($model);
		if (true || Module::hasAccess($module->id)) {
			$found = ("\\App\\Models\\" . $module->model)::find($id);
			if ($found != null) {

				$module->row = $found;

				foreach ($module->app_fields as $f) {
					$this->setPopup($module->row, $f, [1]);
				}

				return $this->successResponse($module);
			} else {
				return $this->errorResponse("MODEL NOT FOUND");
			}
		} else {
			return $this->errorResponse("NOT ALLOWED");
		}
	}

	/**
	 * Update the specified clientchildren in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $modelName, $id)
	{

		if (true || Module::hasAccess($modelName, "edit")) {

			$rules = Module::validateRules($modelName, $request, true);

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
				return $validator->errors();
			}

			$insert_id = Module::updateRow($modelName, $request, $id);
			$newModel = ("\\App\\Models\\" . Module::getByTable($modelName)->model)::find($insert_id);
			return $this->successResponse($newModel);
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}

	/**
	 * Remove the specified clientchildren from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($modelName, $id)
	{
		$module = Module::getByTable($modelName);

		if (true || Module::hasAccess("ClientChildrens", "delete")) {
			$deleteModel = ("\\App\\Models\\" . $module->model)::find($id);
			$deleteModel->delete();
			// Redirecting to index() method
			return $this->successResponse($id);
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}

	/**
	 * Remove the specified clientchildren from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function multiDelete(Request $request, $modelName)
	{
		$module = Module::getByTable($modelName);

		if (true || Module::hasAccess("ClientChildrens", "delete")) {
			$deleteModel = ("\\App\\Models\\" . $module->model)::whereIn("id", $request->ids);
			$deleteModel->delete();
			// Redirecting to index() method
			return $this->successResponse($request->ids, "Deleted successfully");
		} else {
			return $this->errorResponse(401, "Unauthorized for this model");
		}
	}
}
