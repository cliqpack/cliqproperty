<?php

namespace App\Traits;

trait HttpResponses
{
    protected function success($data, $message = null, $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
        return response()->json($response, $code);
    }

    protected function error($message = null, $errors = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];
        return response()->json($response, $code);
    }
}
