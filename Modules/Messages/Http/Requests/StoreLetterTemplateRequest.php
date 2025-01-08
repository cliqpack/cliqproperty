<?php

namespace Modules\Messages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\HttpResponses;

class StoreLetterTemplateRequest extends FormRequest
{
    use HttpResponses;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'message_action_name_id' => 'required|exists:message_action_names,id',
            'message_trigger_to_id' => 'required|exists:message_action_trigger_tos,id',
            'messsage_trigger_point_id' => 'required|exists:message_action_trigger_points,id',
            'status' => 'required|boolean',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $response = $this->error('Invalid data send', $errors->messages(), 422);
        throw new HttpResponseException($response);

    }
}
