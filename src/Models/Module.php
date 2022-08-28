<?php

namespace DesarrollatuApp\NWCRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;
use Log;
use DB;
use DesarrollatuApp\NWCRM\Helpers\LAHelper;
use Image;
use App\Models\Upload;
use Auth;
use Carbon\Carbon;

class Module extends Model
{
	protected $table = 'modules';

	protected $fillable = [
		"name", "name_db", "label", "view_col", "model", "controller", "is_gen", "fa_icon"
	];

	protected $hidden = [];

	public function app_fields()
	{
		return $this->hasMany(ModuleFields::class, "module")->where("show_in_app", true);
	}

	public function fields()
	{
		return $this->hasMany(ModuleFields::class, "module");
	}

	public static function generateBase($module_name, $icon)
	{

		$names = LAHelper::generateModuleNames($module_name, $icon);

		// Check is Generated
		$is_gen = false;
		if (file_exists(base_path('app/Http/Controllers/' . ($names->controller) . ".php"))) {
			if (($names->model == "User" || $names->model == "Role" || $names->model == "Permission") && file_exists(base_path('app/' . ($names->model) . ".php"))) {
				$is_gen = true;
			} else if (file_exists(base_path('app/Models/' . ($names->model) . ".php"))) {
				$is_gen = true;
			}
		}
		$module = Module::where('name', $names->module)->first();
		if (!isset($module->id)) {
			$module = Module::create([
				'name' => $names->module,
				'label' => $names->label,
				'name_db' => $names->table,
				'view_col' => "",
				'model' => $names->model,
				'controller' => $names->controller,
				'fa_icon' => $names->fa_icon,
				'is_gen' => $is_gen,

			]);
		}
		return $module->id;
	}

	public static function generate($module_name, $module_name_db, $view_col, $faIcon = "fa-cube", $fields)
	{

		$names = LAHelper::generateModuleNames($module_name, $faIcon);
		$fields = Module::format_fields($fields);

		if (substr_count($view_col, " ") || substr_count($view_col, ".")) {
			throw new Exception("Unable to generate migration for " . ($names->module) . " : Invalid view_column_name. 'This should be database friendly lowercase name.'", 1);
		} else if (!Module::validate_view_column($fields, $view_col)) {
			throw new Exception("Unable to generate migration for " . ($names->module) . " : view_column_name not found in field list.", 1);
		} else {
			// Check is Generated
			$is_gen = false;
			if (file_exists(base_path('app/Http/Controllers/' . ($names->controller) . ".php"))) {
				if (($names->model == "User" || $names->model == "Role" || $names->model == "Permission") && file_exists(base_path('app/' . ($names->model) . ".php"))) {
					$is_gen = true;
				} else if (file_exists(base_path('app/Models/' . ($names->model) . ".php"))) {
					$is_gen = true;
				}
			}

			$module = Module::where('name', $names->module)->first();
			if (!isset($module->id)) {
				$module = Module::create([
					'name' => $names->module,
					'label' => $names->label,
					'name_db' => $names->table,
					'view_col' => $view_col,
					'model' => $names->model,
					'controller' => $names->controller,
					'is_gen' => $is_gen,
					'fa_icon' => $faIcon
				]);
			}

			$ftypes = ModuleFieldTypes::getFTypes();
			//print_r($ftypes);
			//print_r($module);
			//print_r($fields);


			Schema::create($names->table, function (Blueprint $table) use ($fields, $module, $ftypes) {

				$table->increments('id');
				foreach ($fields as $field) {

					$mod = ModuleFields::where('module', $module->id)->where('colname', $field->colname)->first();
					if (!isset($mod->id)) {
						if ($field->field_type == "Multiselect" || $field->field_type == "Taginput") {

							if (is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
								$field->defaultvalue = json_decode($field->defaultvalue);
							}

							if (is_string($field->defaultvalue) || is_int($field->defaultvalue)) {
								$dvalue = json_encode([$field->defaultvalue]);
							} else {
								$dvalue = json_encode($field->defaultvalue);
							}
						} else {
							$dvalue = $field->defaultvalue;
							if (is_string($field->defaultvalue) || is_int($field->defaultvalue)) {
								$dvalue = $field->defaultvalue;
							} else if (is_array($field->defaultvalue) && is_object($field->defaultvalue)) {
								$dvalue = json_encode($field->defaultvalue);
							}
						}


						$pvalues = $field->popup_vals;
						if (is_array($field->popup_vals) || is_object($field->popup_vals)) {
							$pvalues = json_encode($field->popup_vals);
						}

						$field_obj = ModuleFields::create([
							'module' => $module->id,
							'colname' => $field->colname,
							'label' => $field->label,
							'field_type' => $ftypes[$field->field_type],
							'unique' => $field->unique,
							'defaultvalue' => $dvalue,
							'minlength' => $field->minlength,
							'maxlength' => $field->maxlength,
							'required' => $field->required,
							'popup_vals' => $pvalues
						]);
						$field->id = $field_obj->id;
						$field->module_obj = $module;
					}

					// Schema::dropIfExists($names->table);

					Module::create_field_schema($table, $field);
				}

				// $table->string('name');
				// $table->string('designation', 100);
				// $table->string('mobile', 20);
				// $table->string('mobile2', 20);
				// $table->string('email', 100)->unique();
				// $table->string('gender')->default('male');
				// $table->integer('dept')->unsigned();
				// $table->integer('role')->unsigned();
				// $table->string('city', 50);
				// $table->string('address', 1000);
				// $table->string('about', 1000);
				// $table->date('date_birth');
				// $table->date('date_hire');
				// $table->date('date_left');
				// $table->double('salary_cur');
				if ($module->name_db == "users") {
					$table->rememberToken();
				}
				$table->softDeletes();
				$table->timestamps();
			});
		}
	}

	public static function validate_view_column($fields, $view_col)
	{
		$found = false;
		foreach ($fields as $field) {
			if ($field->colname == $view_col) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	public static function create_field_schema($table, $field, $update = false, $isFieldTypeChange = false)
	{

		if (is_numeric($field->field_type)) {
			$ftypes = ModuleFieldTypes::getFTypes();
			$field->field_type = array_search($field->field_type, $ftypes);
		}
		if (!is_string($field->defaultvalue)) {
			$defval = json_encode($field->defaultvalue);
		} else {
			$defval = $field->defaultvalue;
		}
		Log::debug('Module:create_field_schema (' . $update . ') - ' . $field->colname . " - " . $field->field_type
			. " - " . $defval . " - " . $field->maxlength);

		switch ($field->field_type) {
			case 'Address':
				if ($update) {
					$var = $table->integer($field->colname)->change();
				} else {
					$var = $table->integer($field->colname);
				}
				if ($field->defaultvalue != "" && is_numeric($field->defaultvalue)) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default(1);
				} else {
					$var->nullable()->default(null);
				}
				break;
			case 'Checkbox':
				if ($update) {
					$var = $table->boolean($field->colname)->change();
				} else {
					$var = $table->boolean($field->colname);
				}
				if ($field->defaultvalue == "true" || $field->defaultvalue == "false" || $field->defaultvalue == true || $field->defaultvalue == false) {
					if (is_string($field->defaultvalue)) {
						if ($field->defaultvalue == "true") {
							$field->defaultvalue = true;
						} else {
							$field->defaultvalue = false;
						}
					}
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$field->defaultvalue = false;
				}
				break;
			case 'Currency':
				if ($update) {
					$var = $table->double($field->colname, 15, 2)->change();
				} else {
					$var = $table->double($field->colname, 15, 2);
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("0.0");
				}
				break;
			case 'Date':
				if ($update) {
					$var = $table->date($field->colname)->change();
				} else {
					$var = $table->date($field->colname);
				}
				if ($field->defaultvalue != "" && !starts_with($field->defaultvalue, "date")) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("1970-01-01");
				} else {
					$var->nullable();
				}
				break;
			case 'Datetime':

				if ($field->defaultvalue != '' && !$field->required) {
					$var->default($field->defaultvalue);
					$var = $table->timestamp($field->colname);
				} else {
					$var = $table->timestamp($field->colname)->default(null)->nullable();
				}

				if ($update) {
					$var->change();
				}

				break;
			case 'Decimal':
				$var = null;
				if ($update) {
					$var = $table->decimal($field->colname, 15, 3)->change();
				} else {
					$var = $table->decimal($field->colname, 15, 3);
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("0.0");
				}
				break;
			case 'Dropdown':


				if ($field->popup_vals == "") {
					if (is_int($field->defaultvalue)) {
						if ($update) {
							$var = $table->integer($field->colname)->unsigned()->change();
						} else {
							$var = $table->integer($field->colname)->unsigned();
						}

						break;
					} else if (is_string($field->defaultvalue)) {
						if ($update) {
							$var = $table->string($field->colname)->change();
						} else {
							$var = $table->string($field->colname);
						}
						break;
					}
				}
				$popup_vals = json_decode($field->popup_vals);
				if (starts_with($field->popup_vals, "@")) {
					$foreign_table_name = str_replace("@", "", $field->popup_vals);
					if ($update) {
						$var = $table->integer($field->colname)->unsigned();
						if ($field->defaultvalue == "" || $field->defaultvalue == "0") {

							if ($field->required)
								$var->default(1);
							else
								$var->default('NULL')->nullable(true);
						} else {
							$var->default($field->defaultvalue);
						}
						$var->change();
						$table->dropForeign($field->module_obj->name_db . "_" . $field->colname . "_foreign");
						$table->foreign($field->colname)->references('id')->on($foreign_table_name);
					} else {
						$var = $table->integer($field->colname)->unsigned();
						if ($field->defaultvalue == "" || $field->defaultvalue == "0") {
							$var->nullable();
						} else {
							$var->default($field->defaultvalue);
						}
						$table->foreign($field->colname)->references('id')->on($foreign_table_name);
					}
				} else if (is_array($popup_vals)) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
					if ($field->defaultvalue != "") {
						$var->default($field->defaultvalue);
					} else if ($field->required) {
						$var->default("");
					}
				} else if (is_object($popup_vals)) {
					// ############### Remaining
					if ($update) {
						$var = $table->integer($field->colname)->unsigned()->change();
					} else {
						$var = $table->integer($field->colname)->unsigned();
					}
					// if(is_int($field->defaultvalue)) {
					//     $var->default($field->defaultvalue);
					//     break;
					// }
				}
				break;
			case 'Email':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname, 100)->change();
					} else {
						$var = $table->string($field->colname, 100);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'File':
				if ($update) {
					$var = $table->integer($field->colname)->change();
				} else {
					$var = $table->integer($field->colname);
				}
				if ($field->defaultvalue != "" && is_numeric($field->defaultvalue)) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default(0);
				}
				break;
			case 'Files':
				if ($update) {
					$var = $table->string($field->colname, 256)->change();
				} else {
					$var = $table->string($field->colname, 256);
				}
				if (is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
					$var->default($field->defaultvalue);
				} else if (is_array($field->defaultvalue)) {
					$var->default(json_encode($field->defaultvalue));
				} else {
					$var->default("[]");
				}
				break;
			case 'Float':
				if ($update) {
					$var = $table->float($field->colname)->change();
				} else {
					$var = $table->float($field->colname);
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("0.0");
				}
				break;
			case 'HTML':
				if ($update) {
					$var = $table->string($field->colname, 10000)->change();
				} else {
					$var = $table->string($field->colname, 10000);
				}
				if ($field->defaultvalue != null) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'Image':
				if ($update) {
					$var = $table->integer($field->colname)->change();
				} else {
					$var = $table->integer($field->colname);
				}
				if ($field->defaultvalue != "" && is_numeric($field->defaultvalue)) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default(1);
				}
				break;
			case 'Integer':
				$var = null;
				if ($update) {
					$var = $table->integer($field->colname, false)->unsigned()->change();
				} else {
					$var = $table->integer($field->colname, false)->unsigned();
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("0");
				}
				break;
			case 'Mobile':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'Multiselect':
				if ($update) {
					$var = $table->string($field->colname, 256)->change();
				} else {
					$var = $table->string($field->colname, 256);
				}
				if (is_array($field->defaultvalue)) {
					$field->defaultvalue = json_encode($field->defaultvalue);
					$var->default($field->defaultvalue);
				} else if (is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
					$var->default($field->defaultvalue);
				} else if ($field->defaultvalue == "" || $field->defaultvalue == null) {
					$var->default("[]");
				} else if (is_string($field->defaultvalue)) {
					$field->defaultvalue = json_encode([$field->defaultvalue]);
					$var->default($field->defaultvalue);
				} else if (is_int($field->defaultvalue)) {
					$field->defaultvalue = json_encode([$field->defaultvalue]);
					//echo "int: ".$field->defaultvalue;
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("[]");
				}
				break;
			case 'Name':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'Password':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				}
				break;
			case 'Radio':
				$var = null;
				if ($field->popup_vals == "") {
					if (is_int($field->defaultvalue)) {
						if ($update) {
							$var = $table->integer($field->colname)->unsigned()->change();
						} else {
							$var = $table->integer($field->colname)->unsigned();
						}
						$var->default($field->defaultvalue);
						break;
					} else if (is_string($field->defaultvalue)) {
						if ($update) {
							$var = $table->string($field->colname)->change();
						} else {
							$var = $table->string($field->colname);
						}
						$var->default($field->defaultvalue);
						break;
					}
				}
				if (is_string($field->popup_vals) && starts_with($field->popup_vals, "@")) {
					if ($update) {
						$var = $table->integer($field->colname)->unsigned()->change();
					} else {
						$var = $table->integer($field->colname)->unsigned();
					}
					break;
				}
				$popup_vals = json_decode($field->popup_vals);
				if (is_array($popup_vals)) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
					if ($field->defaultvalue != "") {
						$var->default($field->defaultvalue);
					} else if ($field->required) {
						$var->default("");
					}
				} else if (is_object($popup_vals)) {
					// ############### Remaining
					if ($update) {
						$var = $table->integer($field->colname)->unsigned()->change();
					} else {
						$var = $table->integer($field->colname)->unsigned();
					}
					// if(is_int($field->defaultvalue)) {
					//     $var->default($field->defaultvalue);
					//     break;
					// }
				}
				break;
			case 'String':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != null) {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'Taginput':
				$var = null;
				if ($update) {
					$var = $table->string($field->colname, 1000)->change();
				} else {
					$var = $table->string($field->colname, 1000);
				}
				if (is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
					$field->defaultvalue = json_decode($field->defaultvalue);
				}

				if (is_string($field->defaultvalue)) {
					$field->defaultvalue = json_encode([$field->defaultvalue]);
					//echo "string: ".$field->defaultvalue;
					$var->default($field->defaultvalue);
				} else if (is_array($field->defaultvalue)) {
					$field->defaultvalue = json_encode($field->defaultvalue);
					//echo "array: ".$field->defaultvalue;
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				}
				break;
			case 'Textarea':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->text($field->colname)->change();
					} else {
						$var = $table->text($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
					if ($field->defaultvalue != "") {
						$var->default($field->defaultvalue);
					} else if ($field->required) {
						$var->default("");
					} else {
						$var->nullable();
					}
				}
				break;
			case 'TextField':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
			case 'URL':
				$var = null;
				if ($field->maxlength == 0) {
					if ($update) {
						$var = $table->string($field->colname)->change();
					} else {
						$var = $table->string($field->colname);
					}
				} else {
					if ($update) {
						$var = $table->string($field->colname, $field->maxlength)->change();
					} else {
						$var = $table->string($field->colname, $field->maxlength);
					}
				}
				if ($field->defaultvalue != "") {
					$var->default($field->defaultvalue);
				} else if ($field->required) {
					$var->default("");
				} else {
					$var->nullable();
				}
				break;
		}

		// set column unique
		if ($update) {
			if ($isFieldTypeChange) {
				if ($field->unique && $var != null && $field->maxlength < 256) {
					$table->unique([\DB::raw("{$field->colname}(191)")]);
				}
			}
		} else {
			if ($field->unique && $var != null && $field->maxlength < 256) {
				$table->unique([\DB::raw("{$field->colname}(191)")]);
			}
		}
	}

	public static function format_fields($fields)
	{
		$out = array();
		foreach ($fields as $field) {
			$obj = (object) array();
			$obj->colname = $field[0];
			$obj->label = $field[1];
			$obj->field_type = $field[2];

			if (!isset($field[3])) {
				$obj->unique = 0;
			} else {
				$obj->unique = $field[3];
			}
			if (!isset($field[4])) {
				$obj->defaultvalue = '';
			} else {
				$obj->defaultvalue = $field[4];
			}
			if (!isset($field[5])) {
				$obj->minlength = 0;
			} else {
				$obj->minlength = $field[5];
			}
			if (!isset($field[6])) {
				$obj->maxlength = 0;
			} else {
				// Because maxlength above 256 will not be supported by Unique
				if ($obj->unique) {
					$obj->maxlength = 250;
				} else {
					$obj->maxlength = $field[6];
				}
			}
			if (!isset($field[7])) {
				$obj->required = 0;
			} else {
				$obj->required = $field[7];
			}
			if (!isset($field[8])) {
				$obj->popup_vals = "";
			} else {
				if (is_array($field[8])) {
					$obj->popup_vals = json_encode($field[8]);
				} else {
					$obj->popup_vals = $field[8];
				}
			}
			$out[] = $obj;
		}
		return $out;
	}

	/**
	 * Get Module by module name
	 * $module = Module::get($module_name);
	 **/
	public static function get($module_name)
	{
		$module = null;
		if (is_int($module_name)) {
			$module = Module::find($module_name);
		} else {
			$module = Module::where('name', $module_name)->first();
		}

		if (isset($module)) {
			$module = $module->toArray();
			$fields = ModuleFields::where('module', $module['id'])->orderBy('sort', 'asc')->get()->toArray();
			$fields2 = array();
			foreach ($fields as $field) {
				$fields2[$field['colname']] = $field;
			}
			$module['fields'] = $fields2;
			return (object) $module;
		} else {
			return null;
		}
	}

	/**
	 * Get Module by table name
	 * $module = Module::getByTable($table_name);
	 **/
	public static function getByTable($table_name, $withFields = false)
	{
		$module = Module::where('name_db', $table_name)->first();
		return $module;
	}

	/**
	 * Get Array for Dropdown, Multiselect, Taginput, Radio from Module getByTable
	 * $array = Module::getDDArray($module_name);
	 **/
	public static function getDDArray($module_name)
	{
		$module = Module::where('name', $module_name)->first();
		if (isset($module)) {
			$model_name = ucfirst(str_singular($module_name));
			if ($model_name == "User" || $model_name == "Role" || $model_name == "Permission") {
				$model = "App\\" . $module->model;
			} else {
				$model = "App\\Models\\" . $module->model;
			}

			$result = $model::all();
			$out = array();
			foreach ($result as $row) {
				$view_col = $module->view_col;
				$out[$row->id] = $row->{$view_col};
			}
			return $out;
		} else {
			return array();
		}
	}

	public static function validateRules($module_name, $request, $isEdit = false)
	{
		$module = Module::where("name", $module_name)->first();

		return $module->ownValidateRules($isEdit);
	}

	public function ownValidateRules($isEdit = false)
	{
		$rules = [];
		$module = $this;

		if (isset($module)) {

			$ftypes = ModuleFieldTypes::getFTypes2();

			foreach ($module->fields as $field) {
				$col = "";
				if ($field['required']) {
					$col .= "required|";
				}
				if (in_array($ftypes[$field['field_type']], array("Currency", "Decimal"))) {
					// No min + max length
				} else {
					if ($field['minlength'] != 0) {
						$col .= "min:" . $field['minlength'] . "|";
					}
					if ($field['maxlength'] != 0) {
						$col .= "max:" . $field['maxlength'] . "|";
					}
				}
				if ($field['unique'] && !$isEdit) {
					$col .= "unique:" . $module->name_db . "," . $field['colname'] . ",NULL,id,deleted_at,NULL";
				}
				if ($col != "") {
					$rules[$field['colname']] = trim($col, "|");
				}
			}
		}
		return $rules;
	}

	public static function insert($module_name, $request)
	{
		$module = Module::get($module_name);
		if (isset($module)) {
			$model_name = ucfirst(str_singular($module_name));
			if ($model_name == "User" || $model_name == "Role" || $model_name == "Permission") {
				$model = "App\\" . $module->model;
			} else {
				$model = "App\\Models\\" . $module->model;
			}

			// Delete if unique rows available which are deleted
			$old_row = null;
			$uniqueFields = ModuleFields::where('module', $module->id)->where('unique', '1')->get()->toArray();
			foreach ($uniqueFields as $field) {
				Log::debug("insert: " . $module->name_db . " - " . $field['colname'] . " - " . $request->{$field['colname']});
				$old_row = DB::table($module->name_db)->whereNotNull('deleted_at')->where($field['colname'], $request->{$field['colname']})->first();
				if (isset($old_row->id)) {
					Log::debug("deleting: " . $module->name_db . " - " . $field['colname'] . " - " . $request->{$field['colname']});
					DB::table($module->name_db)->whereNotNull('deleted_at')->where($field['colname'], $request->{$field['colname']})->delete();
				}
			}

			$row = new $model;
			if (isset($old_row->id)) {
				// To keep old & new row id remain same
				$row->id = $old_row->id;
			}
			$row = Module::processDBRow($module, $request, $row);
			$row->save();
			return $row->id;
		} else {
			return null;
		}
	}

	public static function updateRow($module_name, $request, $id)
	{
		$module = Module::get($module_name);
		if (isset($module)) {
			$model_name = ucfirst(str_singular($module_name));
			if ($model_name == "User" || $model_name == "Role" || $model_name == "Permission") {
				$model = "App\\" . ucfirst(str_singular($module_name));
			} else {
				$model = "App\\Models\\" . ucfirst(str_singular($module_name));
			}
			//$row = new $module_path;
			$row = $model::find($id);
			$row = Module::processDBRow($module, $request, $row);
			
			$row->save();
			return $row->id;
		} else {
			return null;
		}
	}

	public static function processDBRow($module, $request, $row)
	{
		$ftypes = ModuleFieldTypes::getFTypes2();

		foreach ($module->fields as $field) {
			if(!$request->has($field['colname']) && $ftypes[$field['field_type']] != "Checkbox") continue;
			
			if (isset($request->{$field['colname']}) || $request->{$field['colname']} == null || isset($request->{$field['colname'] . "_hidden"})) {

				switch ($ftypes[$field['field_type']]) {
					case 'Checkbox':
						#TODO: Bug fix
						if (isset($request->{$field['colname']})) {
							$row->{$field['colname']} = $request->{$field['colname']} !== false && $request->{$field['colname']} !== "false" && $request->{$field['colname']} !== 0;
						} else if (isset($request->{$field['colname'] . "_hidden"})) {
							$row->{$field['colname']} = false;
						}
						break;
					case 'Date':
						if ($request->{$field['colname']} != "") {
							$date = $request->{$field['colname']};
							if (str_contains($date, "/")) {
								$d2 = Carbon::createFromFormat("d/m/Y", $date);
							} else {
								$d2 = Carbon::parse($date)->setTimezone('UTC');
							}
							$request->{$field['colname']} = $d2;
						}
						$row->{$field['colname']} = $request->{$field['colname']};
						break;
					case 'Datetime':
						#TODO: Bug fix
						if ($request->{$field['colname']} != "") {
							$date = $request->{$field['colname']};

							if (str_contains($date, "/")) {
								$d2 = Carbon::createFromFormat("d/m/Y h:i A", $date);
							} else {
								$d2 = Carbon::parse($date)->setTimezone('UTC');
							}
							$request->{$field['colname']} = $d2;
						}
						$row->{$field['colname']} = $request->{$field['colname']};
						break;
					case 'Multiselect':
						#TODO: Bug fix
						$row->{$field['colname']} = is_scalar($request->{$field['colname']}) ? json_decode($request->{$field['colname']}, true) : $request->{$field['colname']};
						break;
					case 'Password':
						$row->{$field['colname']} = bcrypt($request->{$field['colname']});
						break;
					case 'Taginput':
						#TODO: Bug fix
						$row->{$field['colname']} = json_encode($request->{$field['colname']});
						break;
					case 'Files':
						$files = json_decode($request->{$field['colname']});
						$files2 = array();
						foreach ($files as $file) {
							$files2[] = "" . $file;
						}
						$row->{$field['colname']} = json_encode($files2);
						break;
					case "Image":
						if ($request->{$field['colname']}) {

							if (filter_var($request->{$field['colname']}, FILTER_VALIDATE_INT) !== false) {
								$row->{$field['colname']} = $request->{$field['colname']};
							} else {
								$folder = storage_path('uploads');
								$date_append = time();
								$filename = $module->name_db . "_" . $date_append . ".png";
								$path = $folder . DIRECTORY_SEPARATOR . $filename;

								$img = Image::make(file_get_contents($request->{$field['colname']}));
								$img->resize(1024, null, function ($constraint) {
									$constraint->aspectRatio();
									$constraint->upsize();
								});
								$img->save($path);
								$upload = Upload::create([
									"name" => $filename,
									"path" => $path,
									"extension" => pathinfo($filename, PATHINFO_EXTENSION),
									"caption" => "",
									"hash" => "",
									"public" => 0,
									"user_id" => Auth::user()->id
								]);

								$row->{$field['colname']} = $upload->id;
							}
						}
						break;
					case 'Dropdown':

						$row->{$field['colname']} = $request->{$field['colname']};
						break;
					default:
						
						$row->{$field['colname']} = $request->{$field['colname']};
						
						break;
				}
			}
		}
		return $row;
	}

	public static function itemCount($module_name)
	{
		$module = Module::get($module_name);
		if (isset($module)) {
			$model_name = ucfirst(str_singular($module_name));
			if ($model_name == "User" || $model_name == "Role" || $model_name == "Permission") {
				if (file_exists(base_path('app/' . $model_name . ".php"))) {
					$model = "App\\" . $model_name;
					return $model::count();
				} else {
					return "Model doesn't exists";
				}
			} else {
				if (file_exists(base_path('app/Models/' . $model_name . ".php"))) {
					$model = "App\\Models\\" . $model_name;
					return $model::count();
				} else {
					return "Model doesn't exists";
				}
			}
		} else {
			return 0;
		}
	}

	/**
	 * Get Module Access for all roles
	 * $roles = Module::getRoleAccess($id);
	 **/
	public static function getRoleAccess($module_id, $specific_role = 0)
	{
		$module = Module::find($module_id);
		$module = Module::get($module->name);

		if ($specific_role) {
			$roles_arr = DB::table('roles')->where('id', $specific_role)->get();
		} else {
			$roles_arr = DB::table('roles')->get();
		}
		$roles = array();

		$arr_field_access = array(
			'invisible' => 0,
			'readonly' => 1,
			'write' => 2
		);

		foreach ($roles_arr as $role) {
			// get Current Module permissions for this role

			$module_perm = DB::table('role_module')->where('role_id', $role->id)->where('module_id', $module->id)->first();
			if (isset($module_perm->id)) {
				// set db values
				$role->view = $module_perm->acc_view;
				$role->create = $module_perm->acc_create;
				$role->edit = $module_perm->acc_edit;
				$role->delete = $module_perm->acc_delete;
			} else {
				$role->view = false;
				$role->create = false;
				$role->edit = false;
				$role->delete = false;
			}

			// get Current Module Fields permissions for this role

			$role->fields = array();
			foreach ($module->fields as $field) {
				// find role field permission
				$field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->first();

				if (isset($field_perm->id)) {
					$field['access'] = $arr_field_access[$field_perm->access];
				} else {
					$field['access'] = 0;
				}
				$role->fields[$field['id']] = $field;
				//$role->fields[$field['id']] = $field_perm->access;
			}
			$roles[] = $role;
		}
		return $roles;
	}

	/**
	 * Get Module Access for role and access type
	 * Module::hasAccess($module_id, $access_type, $user_id);
	 **/
	public static function hasAccess($module_id, $access_type = "view", $user_id = 0)
	{
		$roles = array();

		if (is_string($module_id)) {
			$module = Module::get($module_id);
			$module_id = $module->id;
		}

		if ($access_type == null || $access_type == "") {
			$access_type = "view";
		}

		if ($user_id) {
			$user = \App\User::find($user_id);
			if (isset($user->id)) {
				$roles = $user->roles();
			}
		} else {
			$roles = \Auth::user()->roles();
		}
		foreach ($roles->get() as $role) {
			$module_perm = DB::table('role_module')->where('role_id', $role->id)->where('module_id', $module_id)->first();
			if (isset($module_perm->id)) {
				if (isset($module_perm->{"acc_" . $access_type}) && $module_perm->{"acc_" . $access_type} == 1) {
					return true;
				} else {
					continue;
				}
			} else {
				continue;
			}
		}
		return false;
	}

	/**
	 * Get Module Field Access for role and access type
	 * Module::hasFieldAccess($module_id, $field_id, $access_type, $user_id);
	 **/
	public static function hasFieldAccess($module_id, $field_id, $access_type = "view", $user_id = 0)
	{
		$roles = array();

		// \Log::debug("module_id: ".$module_id." field_id: ".$field_id." access_type: ".$access_type);

		if (\Auth::guest()) {
			return false;
		}

		if (is_string($module_id)) {
			$module = Module::get($module_id);
			$module_id = $module->id;
		}

		if (is_string($field_id)) {
			$field_object = ModuleFields::where('module', $module_id)->where('colname', $field_id)->first();
			$field_id = $field_object->id;
		}

		if ($access_type == null || $access_type == "") {
			$access_type = "view";
		}

		if ($user_id) {
			$user = \App\User::find($user_id);
			if (isset($user->id)) {
				$roles = $user->roles();
			}
		} else {
			$roles = \Auth::user()->roles();
		}

		$hasModuleAccess = false;

		foreach ($roles->get() as $role) {
			$module_perm = DB::table('role_module')->where('role_id', $role->id)->where('module_id', $module_id)->first();
			if (isset($module_perm->id)) {
				if ($access_type == "view" && isset($module_perm->{"acc_" . $access_type}) && $module_perm->{"acc_" . $access_type} == 1) {
					$hasModuleAccess = true;
					break;
				} else if ($access_type == "write" && ((isset($module_perm->{"acc_create"}) && $module_perm->{"acc_create"} == 1) || (isset($module_perm->{"acc_edit"}) && $module_perm->{"acc_edit"} == 1))) {
					$hasModuleAccess = true;
					break;
				} else {
					continue;
				}
			} else {
				continue;
			}
		}
		if ($hasModuleAccess) {
			$module_field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field_id)->first();
			if (isset($module_field_perm->access)) {
				if ($access_type == "view" && ($module_field_perm->{"access"} == "readonly" || $module_field_perm->{"access"} == "write")) {
					return true;
				} else if ($access_type == "write" && $module_field_perm->{"access"} == "write") {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
		return false;
	}

	/**
	 * Get Module Access for all roles
	 * Module::setDefaultRoleAccess($module_id, $role_id);
	 **/
	public static function setDefaultRoleAccess($module_id, $role_id, $access_type = "readonly")
	{
		$module = Module::find($module_id);
		$module = Module::get($module->name);

		Log::debug('Module:setDefaultRoleAccess (' . $module_id . ', ' . $role_id . ', ' . $access_type . ')');

		$role = DB::table('roles')->where('id', $role_id)->first();

		$access_view = 0;
		$access_create = 0;
		$access_edit = 0;
		$access_delete = 0;
		$access_fields = "invisible";

		if ($access_type == "full") {
			$access_view = 1;
			$access_create = 1;
			$access_edit = 1;
			$access_delete = 1;
			$access_fields = "write";
		} else if ($access_type == "readonly") {
			$access_view = 1;
			$access_create = 0;
			$access_edit = 0;
			$access_delete = 0;

			$access_fields = "readonly";
		}

		$now = date("Y-m-d H:i:s");

		// 1. Set Module Access

		$module_perm = DB::table('role_module')->where('role_id', $role->id)->where('module_id', $module->id)->first();
		if (!isset($module_perm->id)) {
			DB::insert('insert into role_module (role_id, module_id, acc_view, acc_create, acc_edit, acc_delete, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?)', [$role->id, $module->id, $access_view, $access_create, $access_edit, $access_delete, $now, $now]);
		} else {
			DB::table('role_module')->where('role_id', $role->id)->where('module_id', $module->id)->update(['acc_view' => $access_view, 'acc_create' => $access_create, 'acc_edit' => $access_edit, 'acc_delete' => $access_delete]);
		}

		// 2. Set Module Fields Access

		foreach ($module->fields as $field) {
			// find role field permission
			$field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->first();
			if (!isset($field_perm->id)) {
				DB::insert('insert into role_module_fields (role_id, field_id, access, created_at, updated_at) values (?, ?, ?, ?, ?)', [$role->id, $field['id'], $access_fields, $now, $now]);
			} else {
				DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->update(['access' => $access_fields]);
			}
		}
	}

	/**
	 * Get Module Access for all roles
	 * Module::setDefaultFieldRoleAccess($field_id, $role_id);
	 **/
	public static function setDefaultFieldRoleAccess($field_id, $role_id, $access_type = "readonly")
	{
		$field = ModuleFields::find($field_id);
		$module = Module::get($field->module);

		$role = DB::table('roles')->where('id', $role_id)->first();

		$access_fields = "invisible";

		if ($access_type == "full") {
			$access_fields = "write";
		} else if ($access_type == "readonly") {
			$access_fields = "readonly";
		}

		$now = date("Y-m-d H:i:s");

		// find role field permission
		$field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field->id)->first();
		if (!isset($field_perm->id)) {
			DB::insert('insert into role_module_fields (role_id, field_id, access, created_at, updated_at) values (?, ?, ?, ?, ?)', [$role->id, $field->id, $access_fields, $now, $now]);
		} else {
			DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field->id)->update(['access' => $access_fields]);
		}
	}
}
