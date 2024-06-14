<?php

namespace App\Services;

use App\Interfaces\VerificationInterface;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    Const SUPPORTED_PROVIDERS = ['Twilio', 'Local'];

    /**
     * @param string $destination
     * @param string $channel
     * @param string $providerName
     * @return void
     * @throws ValidationException
     */
    public function sendVerificationCode(string $destination, string $channel, string $providerName): void
    {
        /*
         * Verify provider and channel
         * Rate limit imposed for sending code
         * Send code
         */
        $provider = $this->verifyProviderAndChannel($providerName, $channel);
        $provider->throttleDelivery($channel, $destination);
        $provider->sendCode($channel, $destination);
    }

    /**
     * @param string $code
     * @param string $destination
     * @param string $providerName
     * @param string $channel
     * @return void
     * @throws ValidationException
     */
    public function confirmCode(string $code, string $destination, string $providerName, string $channel): void
    {
        /*
         * Verify provider and channel
         * Confirm code
         */
        $provider = $this->verifyProviderAndChannel($providerName, $channel);
        $provider->verifyCode($code, $channel, $destination);
    }

    /**
     * @param string $code
     * @param string $destination
     * @param string $providerName
     * @param string $channel
     * @return bool
     * @throws ValidationException
     */
    public function isCodeVerified(string $code, string $destination, string $providerName, string $channel): bool
    {
        /*
         * Verify provider and channel
         * Verify code
         */
        $provider = $this->verifyProviderAndChannel($providerName, $channel);
        return $provider->isCodeVerified($code, $channel, $destination);
    }

    /**
     * @param string $providerName
     * @param string $channel
     * @return VerificationInterface
     * @throws ValidationException
     */
    public function verifyProviderAndChannel(string $providerName, string $channel): VerificationInterface
    {
        if (!in_array($providerName, self::SUPPORTED_PROVIDERS)) {
            throw ValidationException::withMessages(['exception1' => 'provider '.$providerName.' is not supported!']);
        }

        $providerService = new ProviderService();
        $provider = $providerService->getProvider($providerName);

        if (!in_array($channel, $provider->getAvailableChannels())) {
            throw ValidationException::withMessages(['exception2' => 'Unable to find a valid communication channel:'. $channel . ' - ' . $providerName]);
        }

        return $provider;
    }


}
