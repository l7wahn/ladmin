<?php

namespace WahnStudios\Laraadmin\Controllers\Api;

use WahnStudios\Laraadmin\Controllers\UserChildController;
use Illuminate\Http\Request;
class ApiUserChildController extends UserChildController
{

    public function index() 
    {        
        
        $elements = ($this->model_namespace.$this->module->model)::get();

        return $this->successResponse([
            "elements" => $elements
        ]);
    }

    public function show($id)
    {
        $instance = ($this->model_namespace.$this->module->model)::find($id);

        if($instance != null)
        {
            return successResponse(["element" => $instance]);
        }
        else 
        {
            return $this->errorResponse(__t("We could not find your element"), 401);
        }
    }

    protected function storeAction(Request $request, $insert_id)
    {
        return $this->successResponse(["id" => $insert_id]);
    }


    protected function storeErrorAction($request)
	{
		return $this->errorResponse(__t("You dont have access to this feature"), 401);
	}


    protected function updateErrorAction(Request $request, $id)
    {
        return $this->noAccessError();
    }

    protected function noAccessError() 
    {
        return $this->errorResponse(__t("You dont have access to this feature"), 401);
    }

    protected function afterDestroy($element) 
    {
        return $this->successResponse($element);
    }

    protected function destroyErrorAction($id) 
	{
		return $this->noAccessError();
	}

    protected function errorResponse($message, $code = 500, $errors = null)
    {
        if($errors == null)
        {
            return response()->json([
                "success" => false,
                "message" => $message
            ], $code);
        }
        else 
        {
            return response()->json([
                "success" => false,
                "message" => $message,
                "errors" => $errors
            ], $code);
        }
    }

    protected function successResponse($data)
    {
        return response()->json([
            "success" => true,
            "data" => $data
        ]);
    }

    function onValidatorError($validator) 
    {
        return $this->errorResponse("Unprocesable Entity", 422, $validator->errors()->all());
    }
}