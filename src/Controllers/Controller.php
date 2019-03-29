<?php

namespace WahnStudios\Laraadmin\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;
use DB;
use Validator;
use Datatables;
use Collective\Html\FormFacade as Form;
use WahnStudios\Laraadmin\Models\Module;
use WahnStudios\Laraadmin\Models\ModuleFields;
class Controller extends BaseController
{
    protected $show_action = true;
	protected $view_col = null;
    protected $listing_cols = null;
    protected $module_name = "";
    protected $view_base = null;
    protected $model_namespace = "\\App\\Models\\";
    protected $module = null;
    
    
    public function __construct() 
    {                
        $this->module = Module::get($this->module_name);       
        $this->checkFields();
		// Field Access of Listing Columns
		if(\WahnStudios\Laraadmin\Helpers\LAHelper::laravel_ver() >= 5.3) {           
			$this->middleware(function ($request, $next) {
				$this->listing_cols = ModuleFields::listingColumnAccessScan($this->module_name, $this->listing_cols);
				return $next($request);
			});
		} else {
			$this->listing_cols = ModuleFields::listingColumnAccessScan($this->module_name, $this->listing_cols);
		}
    }

    private function checkFields() 
    {       
        $this->view_col = $this->view_col == null ? $this->module->view_col : $this->view_col;
        $this->view_base = $this->view_base == null ?  config("laraadmin.defaults.viewsfolder"): $this->view_base;
    }

	/**
	 * Display a listing of the Modules.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		if(Module::hasAccess($this->module->id)) {
			return View($this->view_base.'.index', [
				'show_actions' => $this->show_action,
				'listing_cols' => $this->listing_cols,
				'module' => $this->module
			]);
		} else {
            return redirect(config('laraadmin.adminRoute')."/");
        }
	}

	/**
	 * Show the form for creating a new instance.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created instance in database.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		if(Module::hasAccess($this->module_name, "create")) {
		
			$rules = Module::validateRules($this->module_name, $request);
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();
			}
			
			$insert_id = Module::insert($this->module_name, $request);
			
			return redirect()->route(config('laraadmin.adminRoute') . '.'.$this->module->name_db.'.index');
			
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Display the specified instance.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		if(Module::hasAccess($this->module_name, "view")) {
			
			$instance = ($this->model_namespace.$this->module->model)::find($id);
			if(isset($instance->id)) {
				$this->module = Module::get($this->module_name);
				$this->module->row = $instance;
				
				return view($this->view_base.'.show', [
					'module' => $this->module,
					'view_col' => $this->view_col,
					'no_header' => true,
					'no_padding' => "no-padding"
				])->with('instance', $instance);
			} else {
				return view('errors.404', [
					'record_id' => $id,
					'record_name' => ucfirst("instance"),
				]);
			}
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Show the form for editing the specified instance.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		if(Module::hasAccess($this->module_name, "edit")) {			
			$instance = ($this->model_namespace.$this->module->model)::find($id);
			if(isset($instance->id)) {	
				$this->module = Module::get($this->module_name);
				
				$this->module->row = $instance;
				
				return view($this->view_base.'.edit', [
					'module' => $this->module,
					'view_col' => $this->view_col,
				])->with('instance', $instance);
			} else {
				return view('errors.404', [
					'record_id' => $id,
					'record_name' => ucfirst("instance"),
				]);
			}
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Update the specified instance in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		if(Module::hasAccess($this->module_name, "edit")) {
			
			$rules = Module::validateRules($this->module_name, $request, true);
			
			$validator = Validator::make($request->all(), $rules);
			
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();;
			}
			
			$insert_id = Module::updateRow($this->module_name, $request, $id);
			
			return redirect()->route(config('laraadmin.adminRoute') . '.'.$this->module->name_db.'.index');
			
		} else {
			return redirect(config('laraadmin.adminRoute')."/");
		}
	}

	/**
	 * Remove the specified instance from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		if(Module::hasAccess($this->module_name, "delete")) {
			($this->model_namespace.$this->module->model)::find($id)->delete();
			
			// Redirecting to index() method
			return redirect()->route(config('laraadmin.adminRoute') . '.'.$this->module->name_db.'.index');
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
		$values = ($this->model_namespace.$this->module->model)::select($this->listing_cols);
		$out = Datatables::of($values)->make();
		$data = $out->getData();

		$fields_popup = ModuleFields::getModuleFields($this->module_name);
		
		for($i=0; $i < count($data->data); $i++) {
			for ($j=0; $j < count($this->listing_cols); $j++) { 
				$col = $this->listing_cols[$j];
				if($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
					$data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
				}
				if($col == $this->view_col) {
					$data->data[$i][$j] = '<a href="'.url(config('laraadmin.adminRoute') . '/'.$this->module->name_db.'/'.$data->data[$i][0]).'/edit">'.$data->data[$i][$j].'</a>';
				}
			}
			
			if($this->show_action) {
				$output = '';
				if(Module::hasAccess($this->module_name, "edit")) {
					$output .= '<a href="'.url(config('laraadmin.adminRoute') . '/'.$this->module->name_db.'/'.$data->data[$i][0].'/edit').'" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
				}
				
				if(Module::hasAccess($this->module_name, "delete")) {
					$output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.'.$this->module->name_db.'.destroy', $data->data[$i][0]], 'method' => 'delete', 'style'=>'display:inline']);
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
