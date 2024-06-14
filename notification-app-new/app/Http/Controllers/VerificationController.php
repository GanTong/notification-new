<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VerificationController extends Controller
{

    /**
     * @param Request $request
     * @param VerificationService $verificationService
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getConfirmationCode(Request $request, VerificationService $verificationService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required',
            'provider' => 'required',
            'destination' => 'required'
        ]);

        if ($validator->fails()) {
           $status = 500;
           $error = $validator->errors();
           $message = 'validation failed';

            $result = [
                'status' => $status,
                'error' => $error,
                'message' => $message
            ];

           return response()->json($result, $result['status']);
        }

        try {

            $verificationService->sendVerificationCode(
                $request->input('destination'),
                $request->input('channel'),
                $request->input('provider')
            );

            $result = [
                'status' => 200,
                'error' => '',
                'message' => 'successfully sent out code'
            ];

        } catch (InvalidArgumentException $e) {
            $result = [
                'status' => 500,
                'message' => 'send code action failed',
                'error' => $e->getMessage()
            ];
        }

        return response()->json($result, $result['status']);
    }

    /**
     * @param Request $request
     * @param VerificationService $verificationService
     * @return JsonResponse
     */
    public function confirmCode(Request $request, VerificationService $verificationService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required',
            'provider' => 'required',
            'destination' => 'required',
            'code' => 'required|max:4'
        ]);

        if ($validator->fails()) {
            $status = 500;
            $error = $validator->errors();
            $message = 'validation failed';

            $result = [
                'status' => $status,
                'error' => $error,
                'message' => $message
            ];

            return response()->json($result, $result['status']);
        }

        try {
            $verificationService->confirmCode(
                $request->input('code'),
                $request->input('destination'),
                $request->input('provider'),
                $request->input('channel')
            );

            $result = [
                'status' => 200,
                'error' => '',
                'message' => 'successfully confirmed code'
            ];
        } catch (ValidationException $e) {
            $result = [
                'status' => 500,
                'message' => 'confirmed code action failed',
                'error' => $e->getMessage()
            ];
        }

        return response()->json($result, $result['status']);
    }

    /**
     * @param Request $request
     * @param VerificationService $verificationService
     * @return JsonResponse
     */
    public function isCodeVerified(Request $request, VerificationService $verificationService)
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required',
            'provider' => 'required',
            'destination' => 'required',
            'code' => 'required|max:4'
        ]);

        if ($validator->fails()) {
            $status = 500;
            $error = $validator->errors();
            $message = 'validation failed';

            $result = [
                'status' => $status,
                'error' => $error,
                'message' => $message
            ];

            return response()->json($result, $result['status']);
        }

        try {
            $verified = $verificationService->isCodeVerified(
                $request->input('code'),
                $request->input('destination'),
                $request->input('provider'),
                $request->input('channel')
            );

            $result = [
                'status' => $verified ? 200 : 404,
                'error' => '',
                'message' => $verified ? 'successfully verified code' : 'code verification failed'
            ];
        } catch (\Exception $e) {
            $result = [
                'status' => 500,
                'message' => 'code verification failed',
                'error' => $e->getMessage()
            ];
        }

        return response()->json($result, $result['status']);
    }
}
