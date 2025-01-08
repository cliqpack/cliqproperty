<?php

namespace Modules\Messages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\HttpResponses;


class EmailForwardRequest extends FormRequest
{
    use HttpResponses;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => 'required|email',
            'to' => 'required|email',
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $response = $this->error('Invalid data send', $errors->messages(), 422);
        throw new HttpResponseException($response);

    }
}
