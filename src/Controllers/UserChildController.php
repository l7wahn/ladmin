<?php
/**
 * Controller genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace WahnStudios\Laraadmin\Controllers;

use WahnStudios\Laraadmin\Controllers\Controller;
use WahnStudios\Laraadmin\Helpers\LAHelper;
use Illuminate\Http\Request;
use App\User;
use App\Role;
use Mail;
use Log;
class UserChildController extends Controller
{	
	protected $password = null;
	protected $sendMailOnStore = false;
	protected $forcePassword = false;

	protected function beforeStore(Request $request) 
	{
		$this->password = $request->has("password") ? $request->password : LAHelper::gen_password();
	}

	protected function storeRules($request)
	{
		$rules = parent::storeRules($request);
		$rules = array_merge($rules, [
			"email" => "required|unique:users",
			"name" => "required"
		]);
		if($request->has("password") || $this->forcePassword)
		{
			$rules["password"] = $this->forcePassword ? "required|confirmed" : "confirmed";			
		}
		return $rules;
	}

	protected function afterStore(Request $request, $newId) 
	{
				
		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'password' => bcrypt($this->password),
			'context_id' => $newId,
			'type' => $this->module->model,
		]);

		$user->detachRoles();
		$role = Role::find($request->role);
		$user->attachRole($role);

		if($this->sendMailOnStore)
			$this->sendStoreMail();
	}

	protected function sendStoreMail(Request $request, $newId) 
	{
		if(env('MAIL_USERNAME') != null && env('MAIL_USERNAME') != "null" && env('MAIL_USERNAME') != "") {
			// Send mail to User his Password
			Mail::send('emails.send_login_cred', ['user' => $user, 'password' => $password], function ($m) use ($user) {
				$m->from('hello@laraadmin.com', 'LaraAdmin');
				$m->to($user->email, $user->name)->subject('LaraAdmin - Your Login Credentials');
			});
		} else {
			Log::info("User created: username: ".$user->email." Password: ".$password);
		}
	}

	protected function afterUpdate(Request $request, $id)
	{
		$user = User::where('context_id', $id)->first();
		$user->name = $request->name;
		$user->save();
		
		// update user role
		$user->detachRoles();
		$role = Role::find($request->role);
		$user->attachRole($role);
	}
}
