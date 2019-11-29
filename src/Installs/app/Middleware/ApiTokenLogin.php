<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Credential;

class ApiTokenLogin
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'api/v1/test/*',
        'api/v1/auth/forgotPassword',
        'api/v1/auth/validatePasswordToken',
        'api/v1/auth/changePassword',
        'api/v1/auth/login',
        'api/v1/auth/loginParents',
        'api/v1/auth/register',    
        'api/v1/auth/apiLoginParents',       
        "api/v1/ping",    
        "api/v1/localization/languages", 
        'api/v1/localization/translations/*',
        'api/v1/whatsapp*',
        "api/v1/auth/createAccount",
        "api/v1/locale/*"
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($this->inExceptArray($request) || $request->isMethod('OPTIONS'))
        {
            return $next($request);
        }
        
        return $this->checkToken($request, $next);
    }

    private function checkToken($request, Closure $next)
    {
       
        $hasToken = $request->headers->has('API-Token') || $request->get("api-key") != null;
        if($hasToken)
        {
            
            $apiToken =  $request->headers->has('API-Token') ? $request->headers->get('API-Token') : $request->get("api-key");
            $platform = $request->headers->get('Platform');

            $token = Credential::where('api_token', $apiToken)->first();   
            if($token != null)
            {
                if($request->headers->has('FCM-Token'))
                {
                    if($request->headers->get('FCM-Token') != $token->fcm_token)
                    {
                        $token->fcm_token = $request->headers->get('FCM-Token');
                        $token->save();
                    }
                }

                $user = $token->user;
                Auth::login($user);
                unset($token->user);
                $user->current_token = $token;
                return $next($request);
            }
        }

        return response()->json(['success' => false], 401);
        
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
