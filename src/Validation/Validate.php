<?php

namespace Charlama\Validation;

use Charlama\Validation\Rules\UniqueRule;
use Rakit\Validation\Validator;
use Charlama\Session\Session;
use Charlama\Http\Request;
use Charlama\Url\Url;

class Validate
{

    private function __construct() {}
    
    public static function make($rules, $json = false)
    {
        $validator = new Validator;
        
        $validator->addValidator('unique', new UniqueRule());

        $validation = $validator->validate($_POST + $_FILES, $rules);
        
        // handling errors
	$errors = $validation->errors();
        
        if ($validation->fails()) {
            if ($json) {
                return ['errors' => $errors->firstOfAll()];
            } else {
                Session::set('errors', $errors);
                Session::set('old', Request::all());
                
                return Url::redirect(Url::previous());
            }
        }
        //return true;
    }
}
