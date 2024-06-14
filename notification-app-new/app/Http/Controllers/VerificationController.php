<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    /**
     * @var VerificationService
     */
    protected $verificationService;

    public function __constructor(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getConfirmationCode(Request $request)
    {
        try {
            $data = $this->validateRequest($request, false);
            $this->verificationService->sendVerificationCode($data['destination'], $data['channel'], $data['provider']);

            $result = [
                'status' => 200,
                'message' => 'successfully sent out code'
            ];
        } catch (\Exception $e) {
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
     * @return JsonResponse
     */
    public function confirmCode(Request $request)
    {
        try {
            $data = $this->validateRequest($request, true);
            $this->verificationService->confirmCode($request['code'], $data['destination'], $data['provider'], $data['channel']);

            $result = [
                'status' => 200,
                'message' => 'successfully confirmed code'
            ];
        } catch (\Exception $e) {
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
     * @return JsonResponse
     */
    public function isCodeVerified(Request $request)
    {
        try {
            $data = $this->validateRequest($request, true);
            $verified = $this->verificationService->isCodeVerified($request['code'], $data['destination'], $data['provider'], $data['channel']);

            $result = [
                'status' => $verified ? 200 : 404,
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

    /**
     * @param Request $request
     * @param bool $codeApplies
     * @return array
     */
    public function validateRequest(Request $request, bool $codeApplies): array
    {
        if ($codeApplies) {
            $validator = Validator::make($request->all(), [
                'destination' => 'required',
                'channel' => 'required',
                'provider' => 'required',
                'code' => 'required'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'destination' => 'required',
                'channel' => 'required',
                'provider' => 'required'
            ]);
        }

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        if ($codeApplies) {
            return [
                'destination'  => $request->input('destination'),
                'channel' => $request->input('channel'),
                'provider' => $request->input('provider'),
                'code' => $request->input('code')
            ];
        } else {
            return [
                'destination'  => $request->input('destination'),
                'channel' => $request->input('channel'),
                'provider' => $request->input('provider'),
            ];
        }
    }
}
