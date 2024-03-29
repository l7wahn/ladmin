<?php
namespace DesarrollatuApp\NWCRM;

use Schema;
use Collective\Html\FormFacade as Form;
use DesarrollatuApp\NWCRM\Models\Module;
use DesarrollatuApp\NWCRM\Models\ModuleFieldTypes;

class LAFormMaker
{
	
	/**
	* Print input field enclosed within form-group
	**/
	public static function input($module, $field_name, $default_val = null, $required2 = null, $class = 'form-control', $params = [])
	{
		// Check Field Write Aceess
		if(Module::hasFieldAccess($module->id, $module->fields[$field_name]['id'], $access_type = "write")) {
			
			$row = null;
			if(isset($module->row)) {
				$row = $module->row;
			}
			
			//print_r($module->fields);
			$label = $module->fields[$field_name]['label'];
			$field_type = $module->fields[$field_name]['field_type'];
			$unique = $module->fields[$field_name]['unique'];
			$defaultvalue = $module->fields[$field_name]['defaultvalue'];
			$minlength = $module->fields[$field_name]['minlength'];
			$maxlength = $module->fields[$field_name]['maxlength'];
			$required = $module->fields[$field_name]['required'];
			$popup_vals = $module->fields[$field_name]['popup_vals'];
			
			if($required2 != null) {
				$required = $required2;
			}
			
			$field_type = ModuleFieldTypes::find($field_type);
			
			$out = '<div class="form-group">';
			$required_ast = "";
			
			if(!isset($params['class'])) {
				$params['class'] = $class;
			}
			if(!isset($params['placeholder'])) {
				$params['placeholder'] = __t('Enter ').__t($label);
			}
			if($minlength) {
				$params['data-rule-minlength'] = $minlength;
			}
			if($maxlength) {
				$params['data-rule-maxlength'] = $maxlength;
			}
			if($unique && !isset($params['unique'])) {
				$params['data-rule-unique'] = "true";
				$params['field_id'] = $module->fields[$field_name]['id'];
				$params['adminRoute'] = config('laraadmin.adminRoute');
				if(isset($row)) {
					$params['isEdit'] = true;
					$params['row_id'] = $row->id;
				} else {
					$params['isEdit'] = false;
					$params['row_id'] = 0;
				}
				$out .= '<input type="hidden" name="_token_'.$module->fields[$field_name]['id'].'" value="'.csrf_token().'">';
			}
			
			if($required && !isset($params['required'])) {
				$params['required'] = $required;
				$required_ast = "*";
			}
			
			switch ($field_type->name) {
				case 'Address':
					$out .= '<label for="'.$field_name.'">'.$label.$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$params['cols'] = 30;
					$params['rows'] = 3;
					$out .= Form::textarea($field_name, $default_val, $params);
					break;
				case 'Checkbox':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					$out .= '<input type="hidden" value="false" name="'.__t($field_name).'_hidden">';
					
					// ############### Remaining
					unset($params['placeholder']);
					unset($params['data-rule-maxlength']);
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$out .= Form::checkbox($field_name, $field_name, $default_val, $params);
					$out .= '<div class="Switch Round On" style="vertical-align:top;margin-left:10px;"><div class="Toggle"></div></div>';
					break;
				case 'Currency':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					unset($params['data-rule-maxlength']);
					$params['data-rule-currency'] = "true";
					$params['min'] = "0";
					$out .= Form::number($field_name, $default_val, $params);
					break;
				case 'Date':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					$dval = $default_val;
					if($default_val != "") {
						$dval = date("d/m/Y", strtotime($default_val));
					}
					
					unset($params['data-rule-maxlength']);
					// $params['data-rule-date'] = "true";
					
					$out .= "<div class='input-group date'>";
					$out .= Form::text($field_name, $dval, $params);
					$out .= "<span class='input-group-addon'><span class='fa fa-calendar'></span></span></div>";
					// $out .= Form::date($field_name, $default_val, $params);
					break;
				case 'Datetime':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					// ############### Remaining
					$dval = $default_val;
					if($default_val != "") {
						$dval = date("d/m/Y h:i A", strtotime($default_val));
					}
					$out .= "<div class='input-group datetime'>";
					$out .= Form::text($field_name, $dval, $params);
					$out .= "<span class='input-group-addon'><span class='fa fa-calendar'></span></span></div>";
					break;
				case 'Decimal':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					unset($params['data-rule-maxlength']);
					$out .= Form::number($field_name, $default_val, $params);
					break;
				case 'Dropdown':
					$out .= '<label for="'.$field_name.'">'.$label.$required_ast.' :</label>';
					
					unset($params['data-rule-maxlength']);
					$params['data-placeholder'] = $params['placeholder'];
					unset($params['placeholder']);
					if(!isset($params["readonly"]))
					$params['rel'] = "select2";

					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					if($popup_vals != "") {
						if(starts_with($popup_vals, ["@"]))
						{

							if(!isset($params["readonly"]))
							{
								$params['data-ajaxurl'] = url("/api/v1/models/popupQuery/" . str_replace("@", "", $popup_vals)."?api_token=".\Auth::user()->api_token);	
								$params['data-newurl'] = url(config("laraadmin.adminRoute")."/".str_replace("@", "", $popup_vals));	
							}
											
							
							if(isset($default_val))
							{
								$module = Module::getByTable(str_replace("@", "", $popup_vals));
								$el = ("\\App\\Models\\".$module->model)::find($default_val);
								if($el != null)
								{
									$popup_vals = array();
									$popup_vals[$el->id] = $el->{$module->view_col};
								}
							}
							$popup_vals = !is_array($popup_vals) ? array() : $popup_vals;

							
						}
						else
						{
							$popup_vals = LAFormMaker::process_values($popup_vals);
						}	
						if($default_val == null)
						{
							$popup_vals[''] = "";
						}
					} else {
						$popup_vals = array();
					}		

					if(isset($params["readonly"]))
					{
						$params["readonly"] = "readonly";
					}
					$out .= Form::select($field_name, $popup_vals, $default_val, $params);
					break;
				case 'Email':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$params['data-rule-email'] = "true";
					$out .= Form::email($field_name, $default_val, $params);
					break;
				case 'File':
					$out .= '<label for="'.$field_name.'" style="display:block;">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					if(!is_numeric($default_val)) {
						$default_val = 0;
					}
					$out .= Form::hidden($field_name, $default_val, $params);

					if($default_val != 0) {
						$upload = \App\Models\Upload::find($default_val);
					}
					if(isset($upload->id)) {
						$out .= "<a class='btn btn-default btn_upload_file hide' file_type='file' selecter='".$field_name."'>Upload <i class='fa fa-cloud-upload'></i></a>
							<a class='uploaded_file' target='_blank' href='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name)."'><i class='fa fa-file-o'></i><i title='".__t("Remove File")."' class='fa fa-times'></i></a>";
					} else {
						$out .= "<a class='btn btn-default btn_upload_file' file_type='file' selecter='".$field_name."'>Upload <i class='fa fa-cloud-upload'></i></a>
							<a class='uploaded_file hide' target='_blank'><i class='fa fa-file-o'></i><i title='Remove File' class='fa fa-times'></i></a>";
					}
					break;

				case 'Files':
					$out .= '<label for="'.$field_name.'" style="display:block;">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					if(is_array($default_val)) {
						$default_val = json_encode($default_val);
					}
					
					$default_val_arr = json_decode($default_val);
					
					if(is_array($default_val_arr) && count($default_val_arr) > 0) {
						$uploadIds = array();
						$uploadImages = "";
						foreach ($default_val_arr as $uploadId) {
							$upload = \App\Models\Upload::find($uploadId);
							if(isset($upload->id)) {
								$uploadIds[] = $upload->id;
								$fileImage = "";
								if(in_array($upload->extension, ["jpg", "png", "gif", "jpeg"])) {
									$fileImage = "<img src='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name."?s=90")."'>";
								} else {
									$fileImage = "<i class='fa fa-file-o'></i>";
								}
								$uploadImages .= "<a class='uploaded_file2' upload_id='".$upload->id."' target='_blank' href='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name)."'>".$fileImage."<i title='".__t("Remove File")."' class='fa fa-times'></i></a>";
							}
						}
						
						$out .= Form::hidden($field_name, json_encode($uploadIds), $params);
						if(count($uploadIds) > 0) {
							$out .= "<div class='uploaded_files'>".$uploadImages."</div>";
						}
					} else {
						$out .= Form::hidden($field_name, "[]", $params);
						$out .= "<div class='uploaded_files'></div>";
					}
					$out .= "<a class='btn btn-default btn_upload_files' file_type='files' selecter='".$field_name."' style='margin-top:5px;'>".__t("Upload")." <i class='fa fa-cloud-upload'></i></a>";
					break;

				case 'Float':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					unset($params['data-rule-maxlength']);
					$out .= Form::number($field_name, $default_val, $params);
					break;
				case 'HTML':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					// ############### Remaining
					//$out .= '<div class="htmlbox" id="htmlbox_'.$field_name.'" contenteditable>'.__($default_val).'</div>';
					$out .= Form::textarea($field_name, $default_val, $params + ['data-type' => 'html']);
					break;
				case 'Image':
					$out .= '<label for="'.$field_name.'" style="display:block;">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					if(!is_numeric($default_val)) {
						$default_val = 0;
					}
					$out .= Form::hidden($field_name, $default_val, $params);

					if($default_val != 0) {
						$upload = \App\Models\Upload::find($default_val);
					}
					if(isset($upload->id)) {
						$out .= "<a class='btn btn-default btn_upload_image hide' file_type='image' selecter='".$field_name."'>".__t("Upload")." <i class='fa fa-cloud-upload'></i></a>
							<div class='uploaded_image'><img src='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name."?s=150")."'><i title='".__t("Remove Image")."' class='fa fa-times'></i></div>";
					} else {
						$out .= "<a class='btn btn-default btn_upload_image' file_type='image' selecter='".$field_name."'>Upload <i class='fa fa-cloud-upload'></i></a>
							<div class='uploaded_image hide'><img src=''><i title='".__t("Remove Image")."' class='fa fa-times'></i></div>";
					}
					
					break;
				case 'Integer':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					unset($params['data-rule-maxlength']);
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					// $params['min'] = "0"; // Required for Non-negative numbers
					$out .= Form::number($field_name, $default_val, $params);
					break;
				case 'Mobile':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$out .= Form::text($field_name, $default_val, $params);
					break;
				case 'Multiselect':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					unset($params['data-rule-maxlength']);
					$params['data-placeholder'] = __t("Select multiple ").str_plural(__t($label));
					unset($params['placeholder']);
					$params['multiple'] = "true";
					$params['rel'] = "select2";
					if($default_val == null) {
						if($defaultvalue != "") {
							$default_val = json_decode($defaultvalue);
						} else {
							$default_val = "";
						}
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = json_decode($row->$field_name);
					}
					
					if($popup_vals != "") {
						$popup_vals = LAFormMaker::process_values($popup_vals);
					} else {
						$popup_vals = array();
					}
					$out .= Form::select($field_name."[]", $popup_vals, $default_val, $params);
					break;
				case 'Name':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$out .= Form::text($field_name, $default_val, $params);
					break;
				case 'Password':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					$out .= Form::password($field_name, $params);
					break;
				case 'Radio':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' : </label><br>';
					
					// ############### Remaining
					unset($params['placeholder']);
					unset($params['data-rule-maxlength']);
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					if(starts_with($popup_vals, "@")) {
						$popup_vals = LAFormMaker::process_values($popup_vals);
						$out .= '<div class="radio">';
						foreach ($popup_vals as $key => $value) {
							$sel = false;
							if($default_val != "" && $default_val == $value) {
								$sel = true;
							}
							$out .= '<label>'.(Form::radio($field_name, $key, $sel)).' '.__t($value).' </label>';
						}
						$out .= '</div>';
						break;
					} else {
						if($popup_vals != "") {
							$popup_vals = array_values(json_decode($popup_vals));
						} else {
							$popup_vals = array();
						}
						$out .= '<div class="radio">';
						foreach ($popup_vals as $value) {
							$sel = false;
							if($default_val != "" && $default_val == $value) {
								$sel = true;
							}
							$out .= '<label>'.(Form::radio($field_name, $value, $sel)).' '.__t($value).' </label>';
						}
						$out .= '</div>';
						break;
					}
				case 'String':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					$out .= Form::text($field_name, $default_val, $params);
					break;
				case 'Taginput':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if(isset($params['data-rule-maxlength'])) {
						$params['maximumSelectionLength'] = $params['data-rule-maxlength'];
						unset($params['data-rule-maxlength']);
					}
					$params['multiple'] = "true";
					$params['rel'] = "taginput";
					$params['data-placeholder'] = "Add multiple ".str_plural(__t($label));
					unset($params['placeholder']);
					
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = json_decode($row->$field_name);
					}
					
					if($default_val == null) {
						$defaultvalue2 = json_decode($defaultvalue);
						if(is_array($defaultvalue2)) {
							$default_val = $defaultvalue;
						} else if(is_string($defaultvalue)) {
							if (strpos($defaultvalue, ',') !== false) {
								$default_val = array_map('trim', explode(",", $defaultvalue));
							} else {
								$default_val = [$defaultvalue];
							}
						} else {
							$default_val = array();
						}
					}
					$default_val = LAFormMaker::process_values($default_val);
					$out .= Form::select($field_name."[]", $default_val, $default_val, $params);
					break;
				case 'Textarea':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					$params['cols'] = 30;
					$params['rows'] = 3;
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$out .= Form::textarea($field_name, $default_val, $params);
					break;
				case 'TextField':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$out .= Form::text($field_name, $default_val, $params);
					break;
				case 'URL':
					$out .= '<label for="'.$field_name.'">'.__t($label).$required_ast.' :</label>';
					
					if($default_val == null) {
						$default_val = $defaultvalue;
					}
					// Override the edit value
					if(isset($row) && isset($row->$field_name)) {
						$default_val = $row->$field_name;
					}
					
					$params['data-rule-url'] = "true";
					$out .= Form::text($field_name, $default_val, $params);
					break;
			}
			$out .= '</div>';
			return $out;
		} else {
			return "";
		}
	}
	
	/**
	* Processes the populated values for Multiselect / Taginput / Dropdown
	* get data from module / table whichever is found if starts with '@'
	**/
	// $values = LAFormMaker::process_values($data);
	public static function process_values($json) {
		$out = array();
		// Check if populated values are from Module or Database Table
		if(is_string($json)) {
			$array = json_decode($json);
			if(is_array($array)) {
				foreach ($array as $value) {
					$out[$value] = $value;
				}
			} else {
				// TODO: Check posibility of comma based pop values.
			}
		} else if(is_array($json)) {
			foreach ($json as $value) {
				$out[$value] = $value;
			}
		}
		return $out;
	}
	
	/**
	* Display field using blade directive @la_display
	**/
	public static function display($module, $field_name, $class = 'form-control')
	{
		// Check Field View Aceess
		if(Module::hasFieldAccess($module->id, $module->fields[$field_name]['id'], $access_type = "view")) {
			
			$fieldObj = $module->fields[$field_name];
			$label = $module->fields[$field_name]['label'];
			$field_type = $module->fields[$field_name]['field_type'];
			$field_type = ModuleFieldTypes::find($field_type);
			
			$row = null;
			if(isset($module->row)) {
				$row = $module->row;
			}
			
			$out = '<div class="form-group">';
			$out .= '<label for="'.$field_name.'" class="col-md-2">'.__t($label).' :</label>';
			
			$value = $row->$field_name;
			
			switch ($field_type->name) {
				case 'Address':
					if($value != "") {
						$value = $value.'<a target="_blank" class="pull-right btn btn-xs btn-primary btn-circle" href="http://maps.google.com/?q='.$value.'" data-toggle="tooltip" data-placement="left" title="Check location on Map"><i class="fa fa-map-marker"></i></a>';
					}
					break;
				case 'Checkbox':
					if($value == 0) {
						$value = "<div class='label label-danger'>".__t("False")."</div>";
					} else {
						$value = "<div class='label label-success'>".__t("True")."</div>";
					}
					break;
				case 'Currency':
					
					break;
				case 'Date':
					$dt = strtotime($value);
					$value = date("d M Y", $dt);
					break;
				case 'Datetime':
					$dt = strtotime($value);
					$value = date("d M Y, h:i A", $dt);
					break;
				case 'Decimal':
					
					break;
				case 'Dropdown':
					$values = LAFormMaker::process_values($fieldObj['popup_vals']);
					
					if(starts_with($fieldObj['popup_vals'], "@")) {
						if($value != 0) {
							$moduleVal = Module::getByTable(str_replace("@", "", $fieldObj['popup_vals']));
							if(isset($moduleVal->id)) {
								$value = "<a href='".url(config("laraadmin.adminRoute")."/".$moduleVal->name_db."/".$value)."' class='label label-primary'>".$values[$value]."</a> ";
							} else {
								$value = "<a class='label label-primary'>".$values[$value]."</a> ";
							}
						} else {
							$value = "None";
						}
					}
					break;
				case 'Email':
					$value = '<a href="mailto:'.$value.'">'.__t($value).'</a>';
					break;
				case 'File':
					if($value != 0) {
						$upload = \App\Models\Upload::find($value);
						if(isset($upload->id)) {
							$value = '<a class="preview" target="_blank" href="'.url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name).'">
							<span class="fa-stack fa-lg"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-file-o fa-stack-1x fa-inverse"></i></span> '.$upload->name.'</a>';
						} else {
							$value = __t('Uploaded file not found.');
						}
					} else {
						$value = __t('No file.');
					}
					break;
				case 'Files':
					if($value != "" && $value != "[]" && $value != "null" && starts_with($value, "[")) {
						$uploads = json_decode($value);
						$uploads_html = "";

						foreach ($uploads as $uploadId) {
							$upload = \App\Models\Upload::find($uploadId);
							if(isset($upload->id)) {
								$uploadIds[] = $upload->id;
								$fileImage = "";
								if(in_array($upload->extension, ["jpg", "png", "gif", "jpeg"])) {
									$fileImage = "<img src='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name."?s=90")."'>";
								} else {
									$fileImage = "<i class='fa fa-file-o'></i>";
								}
								// $uploadImages .= "<a class='uploaded_file2' upload_id='".$upload->id."' target='_blank' href='".url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name)."'>".$fileImage."<i title='Remove File' class='fa fa-times'></i></a>";
								$uploads_html .= '<a class="preview" target="_blank" href="'.url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name).'" data-toggle="tooltip" data-placement="top" data-container="body" style="display:inline-block;margin-right:5px;" title="'.$upload->name.'">
										'.$fileImage.'</a>';
							}
						}
						$value = $uploads_html;
					} else {
						$value = __t('No files found.');
					}
					break;
				case 'Float':
					
					break;
				case 'HTML':
					break;
				case 'Image':
					if($value != 0) {
						$upload = \App\Models\Upload::find($value);
					}
					if(isset($upload->id)) {
						$value = '<a class="preview" target="_blank" href="'.url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name).'"><img src="'.url("files/".$upload->hash.DIRECTORY_SEPARATOR.$upload->name."?s=150").'"></a>';
					} else {
						$value = __t('Uplaoded image not found.');
					}
					break;
				case 'Integer':
					
					break;
				case 'Mobile':
					$value = '<a target="_blank" href="tel:'.$value.'">'.$value.'</a>';
					break;
				case 'Multiselect':
					$valueOut = "";
					$values = LAFormMaker::process_values($fieldObj['popup_vals']);
					if(count($values)) {
						if(starts_with($fieldObj['popup_vals'], "@")) {
							$moduleVal = Module::getByTable(str_replace("@", "", $fieldObj['popup_vals']));
							$valueSel = json_decode($value);
							foreach ($values as $key => $val) {
								if(in_array($key, $valueSel)) {
									$valueOut .= "<a href='".url(config("laraadmin.adminRoute")."/".$moduleVal->name_db."/".$key)."' class='label label-primary'>".$val."</a> ";
								}
							}
						} else {
							$valueSel = json_decode($value);
							foreach ($values as $key => $val) {
								if(in_array($key, $valueSel)) {
									$valueOut .= "<span class='label label-primary'>".$val."</span> ";
								}
							}
						}
					}
					$value = $valueOut;
					break;
				case 'Name':
					
					break;
				case 'Password':
					$value = '<a href="#" data-toggle="tooltip" data-placement="top" data-container="body" title="Cannot be declassified !!!">********</a>';
					break;
				case 'Radio':
					
					break;
				case 'String':
					
					break;
				case 'Taginput':
					$valueOut = "";
					$values = LAFormMaker::process_values($fieldObj['popup_vals']);
					if(count($values)) {
						if(starts_with($fieldObj['popup_vals'], "@")) {
							$moduleVal = Module::getByTable(str_replace("@", "", $fieldObj['popup_vals']));
							$valueSel = json_decode($value);
							foreach ($values as $key => $val) {
								if(in_array($key, $valueSel)) {
									$valueOut .= "<a href='".url(config("laraadmin.adminRoute")."/".$moduleVal->name_db."/".$key)."' class='label label-primary'>".$val."</a> ";
								}
							}
						} else {
							$valueSel = json_decode($value);
							foreach ($valueSel as $key => $val) {
								$valueOut .= "<span class='label label-primary'>".$val."</span> ";
							}
						}
					} else {
						$valueSel = json_decode($value);
						foreach ($valueSel as $key => $val) {
							$valueOut .= "<span class='label label-primary'>".$val."</span> ";
						}
					}
					$value = $valueOut;
					break;
				case 'Textarea':
					
					break;
				case 'TextField':
					
					break;
				case 'URL':
					$value = '<a target="_blank" href="'.$value.'">'.$value.'</a>';
					break;
			}
			
			$out .= '<div class="col-md-10 fvalue">'.$value.'</div>';
			$out .= '</div>';
			return $out;
		} else {
			return "";
		}
	}
	
	/**
	* Print form using blade directive @la_form
	**/
	public static function form($module, $fields = [])
	{
		if(count($fields) == 0) {
			$fields = array_keys($module->fields);
		}
		$out = "";
		foreach ($fields as $field) {
			$out .= LAFormMaker::input($module, $field);
		}
		return $out;
	}
	
	/**
	* Check Whether User has Module Access
	**/
	public static function la_access($module_id, $access_type = "view", $user_id = 0)
	{
		return Module::hasAccess($module_id, $access_type, $user_id);
	}
	
	/**
	* Check Whether User has Module Field Access
	**/
	public static function la_field_access($module_id, $field_id, $access_type = "view", $user_id = 0)
	{
		return Module::hasFieldAccess($module_id, $field_id, $access_type, $user_id);
	}
}