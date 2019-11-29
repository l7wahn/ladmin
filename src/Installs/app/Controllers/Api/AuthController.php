<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Dwij\Laraadmin\Models\Module;
use App\User;
use Mail;
use Log;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Entities\AppData;
use App\Models\Account;
use App\Models\ClientSchool;
use App\Models\Credential;

class AuthController extends Controller
{

    const VALID_MINUTES = 5;

    public function api_post_Login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
                //'platform' => 'required',
                //'fcm_token' => 'required'
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $o['success'] = Auth::attempt($request->only('email', 'password'));
            if ($o['success'] === true) {
                $user = Auth::user();
                $user->acceptDevice($request->platform, $request->fcm_token);
                $context = ($user->type)::find($user->context_id);
                $context->type = str_replace("App\\Models\\", "", $user->type);
                $appData = AppData::fromUser($user);
                $appData->context = $context;
                return $o + [
                    "data" => $appData
                ];
            } else {
                throw new AuthenticationException("Failed");
            }
        } catch (ValidationException $e) {
            return response()->json(["success" => false, "error" => $validator->errors()], 400);
        } catch (AuthenticationException $e) {
            return response()->json(["success" => false, "error" => trans('auth.failed')], 400);
        } catch (\Exception $e) {
            if (env("APP_DEBUG"))
                return response()->json(["success" => false, "error" => $e->getMessage()], 500);
            else
                return response()->json(["success" => false, "error" => trans('Error on Server')], 500);
        }
    }

    public function api_post_createAccount(Request $request)
    {

        
        $validator = Validator::make($request->all(), [
            "name" => 'required',
            'email' => 'required',
            'password' => 'required|confirmed:min:6',
            "owner_name" => 'required',
            "document_number" => 'required',
            "expected_vehicles" => 'required'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $account = Account::newAccount($request->all());
        $o["success"] = true;
        $user = $account->owner->user;
        $user->acceptDevice($request->platform, $request->fcm_token);
        $context = ($user->type)::find($user->context_id);
        $context->type = str_replace("App\\Models\\", "", $user->type);
        $appData = AppData::fromUser($user);
        $appData->context = $context;
        return $o + [
            "data" => $appData
        ];
    }

    public function api_get_Logout()
    {


        Auth::user()->current_token->delete();

        return response()->json(["success" => true]);
    }

    public function api_post_ChangePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $reset = \DB::table('password_resets')->where([['email', $request->email], ['token', $request->token]])->first();

            if ($reset != null) {

                $user = User::where("email", $request->email)->first();
                $user->password = bcrypt($request->password);
                $user->save();


                \DB::table("password_resets")->where([['email', $request->email], ['token', $request->token]])->delete();

                $o = ['success' => true];
            } else {
                $o = ['success' => false, "error" => "Invalid token"];
            }
            return response()->json($o);
        } catch (ValidationException $e) {
            return response()->json(["success" => false, "error" => $validator->errors()], 400);
        } catch (\Exception $e) {
            if (env("APP_DEBUG"))
                return response()->json(["success" => false, "error" => $e->getMessage()], 500);
            else
                return response()->json(["success" => false, "error" => "Error on server"], 500);
        }
    }

    public function api_post_ValidatePasswordToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = \DB::table('password_resets')->where([['email', $request->email], ['token', $request->token]])->first();

            if ($user != null) {
                if (Carbon::now()->diffInMinutes($user->created_at) > self::VALID_MINUTES) {
                    $o = ['success' => false, "error" => "Token timeout"];
                } else {
                    $o = ['success' => true];
                }
            } else {
                $o = ['success' => false, "error" => "Invalid token"];
            }
            return response()->json($o);
        } catch (ValidationException $e) {
            return response()->json(["success" => false, "error" => $validator->errors()], 400);
        } catch (\Exception $e) {
            if (env("APP_DEBUG"))
                return response()->json(["success" => false, "error" => $e->getMessage()], 500);
            else
                return response()->json(["success" => false, "error" => "Error on server"], 500);
        }
    }

    public function api_post_ForgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            $now = Carbon::now();

            \DB::table('password_resets')->where('email', $request->email)->delete();

            \DB::table('password_resets')->insert([
                'token' => random_int(100000, 999999),
                'email' => $request->email,
                'created_at' => $now
            ]);

            $o['success'] = true;
            $o['created_date'] = $now;
            $o['valid_for'] = self::VALID_MINUTES;

            $o['data'] = [
                'message' => trans("Hemos envíado a su correo un código de recuperación de clave")
            ];

            return $o;
        } catch (ValidationException $e) {
            return response()->json(["success" => false, "error" => $validator->errors()], 400);
        } catch (\Exception $e) {
            if (env("APP_DEBUG"))
                return response()->json(["success" => false, "error" => $e->getMessage()], 500);
            else
                return response()->json(["success" => false, "error" => "Error on server"], 500);
        }
    }

    public function api_post_apiLoginParents(Request $request)
    {
        try {
            $user = Auth::user();
            $checkClient = ClientSchool::where([["client_id", $user->context_id], ["type", "Parent"]])->first();
            if ($checkClient === null) {
                return response("Unauthorized", 401);
            }
            $user->apiToken = $request->headers->get('API-Token');
            $user->fcmToken = $request->headers->get('FCM-Token');
            return response()->json([
                "success" => true,
                "data" => AppData::fromUser($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function api_post_ApiLogin(Request $request)
    {
        try {
            $user = Auth::user();
            $user->acceptDevice("PC");
            return response()->json([
                "success" => true,
                "data" => AppData::fromUser($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function api_post_Register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:users|unique:players',
                'password' => 'required|min:6', //|confirmed',
                "nickname" => "required|unique:players"
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::insert("Players", $request);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'context_id' => $insert_id,
                'type' => "Player",
            ]);

            $user->acceptDevice($request->platform, $request->fcm_token);

            $o = [
                "success" => true,
                "user" => AppData::fromUser($user),
            ];

            /*if(env('MAIL_USERNAME') != null && env('MAIL_USERNAME') != "null" && env('MAIL_USERNAME') != "") {
				// Send mail to User his Password
				Mail::send('emails.send_login_cred', ['user' => $user, 'password' => $password], function ($m) use ($user) {
					$m->from('hello@laraadmin.com', 'LaraAdmin');
					$m->to($user->email, $user->name)->subject('LaraAdmin - Your Login Credentials');
				});
			} else {
				Log::info("User created: username: ".$user->email." Password: ".$password);
			}*/
            return response()->json($o);
        } catch (ValidationException $e) {
            return response()->json(["success" => false, "error" => $validator->errors()], 400);
        } catch (\Exception $e) {
            if (env("APP_DEBUG"))
                return response()->json(["success" => false, "error" => $e->getMessage()], 500);
            else
                return response()->json(["success" => false, "error" => __("Error on server")], 500);
        }
    }

    public function api_get_Own()
    {
        return response()->json(Auth::user());
    }

    public function api_post_FinishRegistration(Request $request)
    {
        $user = \App\User::find(Auth::user()->id);

        $user->name = $request->name;
        $user->password = bcrypt($request->password);

        $user->save();
        Module::updateRow("Clients", $request, $user->client->id);
        $user->client->invitation_code = null;
        $user->client->save();

        return response()->json(["success" => true]);
    }

    public function api_get_checkCredentials()
    {
        $user = Auth::user();
        $context = ($user->type)::find($user->context_id);
        $context->type = str_replace("App\\Models\\", "", $user->type);
        return ["success" => true, "data" => [
            "context" => $context
        ]];
    }
}
