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
use App\Models\Language;
use App\Models\Text;

class InternationalizationController extends Controller
{
    public function api_get_language($_iso)
    {
        $lang = Language::checkByIso($_iso);
        if(file_exists($lang->languageFilePath()))
        {
            return file_get_contents($lang->languageFilePath());
        }
        else return $lang->translatedArray();
    }

    public function api_post_reportWords(Request $request)
    {
        Text::checkStringsAndInsertTheNew($request->words);

        return ["success" => true];
    }
}