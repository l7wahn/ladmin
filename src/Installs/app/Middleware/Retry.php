<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as ServerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class Retry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {   
        $retryable = $request->headers->has("Retry-Status") && $request->headers->get("Retry-Status") == "capable";
        if(!$retryable || !Auth::check())
        {
            return $next($request);
        }

        if($request->headers->has("Retry-Attempt"))
        {
            return $this->retry($request, $next);
        }
        else 
        {
            return $this->runAndSave($request, $next);
        }
    }

    public function retry($request, Closure $next, $guard = null)
    {
        $attempt = $request->headers->get("Retry-Attempt");
        
        $serverRequest = ServerRequest::where('user_id', Auth::user()->id)->where("url", $request->route()->getActionName())->orderBy("created_at", "desc")->first(); 
        
        if($serverRequest == null)
        {
            return $this->runAndSave($request, $next);
        }
        
        if($serverRequest->completed)
        {
            $serverRequest->retried = $attempt;
            $serverRequest->save();
            $headers = json_decode($serverRequest->headers, true);
            $headers["Retry-ID"] = $serverRequest->id;
            return response($serverRequest->payload, $serverRequest->status, $headers);
        } 
        else 
        {
            $error = false;
            $perform = [];
            try {
                $perform = $next($request);
            } catch (\Throwable $th) {
                $error = true;
                $perform = $th;
            }


            $serverRequest->retried = $attempt;
            $serverRequest->payload = $error ? $perform->getMessage() : $perform->getContent();
            $serverRequest->completed = !$error;            
            $serverRequest->headers = $perform->headers;
            $serverRequest->completed = !$error;
            
            $serverRequest->save(); 

            if($error)
            return response($perform)->header("Retry-ID", $serverRequest->id);
            else 
            return $perform->header("Retry-ID", $serverRequest->id);
        }
    }

    public function runAndSave($request, Closure $next, $guard = null)
    {
        $error = false;
        $perform = [];
        try {
            $perform = $next($request);
        } catch (\Throwable $th) {
            $error = true;
            $perform = $th;
        }
        
        $serverRequest = new ServerRequest();        
        $serverRequest->user_id = Auth::user()->id;
        $serverRequest->method = $request->method();
        $serverRequest->url = $request->route()->getActionName();
        $serverRequest->payload = $error ? $perform->getMessage() : $perform->getContent();
        $serverRequest->status = $perform->status();
        $serverRequest->headers = json_encode($perform->headers->all());
        $serverRequest->completed = !$error;
        $serverRequest->save();  
        
        if($error)
        return response($perform)->header("Retry-ID", $serverRequest->id);
        else return $perform->header("Retry-ID", $serverRequest->id);

    }
}
