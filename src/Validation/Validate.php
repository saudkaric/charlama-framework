<?php declare(strict_types=1);

namespace Charlama\Validation;

use Charlama\Url\Url;
use Charlama\Http\Request;
use Charlama\Session\Session;
use Rakit\Validation\Validator;
use Charlama\Validation\Rules\UniqueRule;

class Validate
{
    public function __construct(){}

    public static function make(array $rules, bool $json = false)
    {
        $validator = new Validator;

        $validator->addValidator('unique', new UniqueRule());

        $validation = $validator->validate($_POST + $_FILES, $rules);
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
    }
}