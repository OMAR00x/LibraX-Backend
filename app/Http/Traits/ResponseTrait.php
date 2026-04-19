<?php

namespace App\Http\Traits;

trait ResponseTrait
{
    public function successResponse($data = [], $message = null, $statusCode = 200)
    {
        $response = [
            'success' => true,
            'status' => 'success',
            'statuscode' => $statusCode,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
    public function errorResponse($message = null, $data = [], $statusCode = 500)
    {
        $response = [
            'success' => false,
            'status' => 'failure',
            'statuscode' => $statusCode,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}
