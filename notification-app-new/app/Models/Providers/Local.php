<?php

namespace App\Models\Providers;

use App\Interfaces\VerificationInterface;
use App\Libraries\RatelimiterLaravel;
use App\Mail\VerifyCodeEmail;
use App\Models\Verification;
use App\Repositories\VerificationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class Local extends Model implements VerificationInterface
{
    const RATE_LIMITER_HIT = 100;
    const RATE_LIMITER_INTERVAL = 'hour';

    /**
     * @return string[]
     */
    public function getAvailableChannels(): array
    {
        return ['email'];
    }

    /**
     * @throws ValidationException
     */
    public function sendCode(string $channel, string $destination): void
    {
        /*
         * Create random code
         */
        $verificationRepository = new VerificationRepository();
        $code = $verificationRepository->createCode();

        /*
         * Store code to DB
         */
        $verification = $verificationRepository->storeCode($channel, $destination, $code);

        /*
         * Send code
         */
        $this->deliverCode($channel, $destination, $verification);

    }

    /**
     * @param string $code
     * @param string $channel
     * @param string $destination
     * @return void
     * @throws ValidationException
     */
    public function verifyCode(string $code, string $channel, string $destination): void
    {

        $verificationRepository = new VerificationRepository();

        /*
         * Find valid entry
         */
        $verification = $verificationRepository->findValid($destination, $channel, $code);

        /*
         * If is not found, throw error
         */
        if (!$verification) {
            throw ValidationException::withMessages(['exception1' => 'Code provided is incorrect or already expired']);
        }

        /*
         * If already confirmed, throw error
         */
        if ($verificationRepository->findConfirmedById($verification->id)) {
            throw ValidationException::withMessages(['exception2' => 'Code provided is already confirmed']);
        }

        /*
         * Confirm code
         */
        $verificationRepository->confirmCode($verification->id);
    }

    /**
     * @param string $code
     * @param string $channel
     * @param string $destination
     * @return bool
     */
    public function isCodeVerified(string $code, string $channel, string $destination): bool
    {
        $verificationRepository = new VerificationRepository();
        return (bool)$verificationRepository->findConfirmed($destination, $channel, $code);
    }

    /**
     * @param string $interval
     * @return string
     */
    public function getRatelimiterMessage(string $interval): string
    {
        return 'you have reached the rate limit, please try again in one ' . $interval;
    }

        /**
     * @param string $channel
     * @param string $destination
     * @return void
     * @throws ValidationException
     */
    public function throttleDelivery(string $channel, string $destination): void
    {
        switch ($channel) {
            case 'email':
                $ratelimiter = new RatelimiterLaravel();
                $ratelimiter->throttle(
                    self::RATE_LIMITER_HIT,
                    self::RATE_LIMITER_INTERVAL,
                    $this->getRatelimiterMessage(self::RATE_LIMITER_INTERVAL)
                );
                break;
            default:
                throw ValidationException::withMessages(['exception' => "No throttle rules defined for ".$channel]);
        }
    }

    /**
     * @param string $channel
     * @param string $destination
     * @param Verification $verification
     * @return void
     * @throws ValidationException
     */
    public function deliverCode(string $channel, string $destination, Verification $verification)
    {
        switch ($channel) {
            case 'email':
                Mail::to($destination)->send(new VerifyCodeEmail($verification));
                break;
            default:
                throw ValidationException::withMessages(['exception' => "Unhandled sending channel for ".$channel]);
        }

    }
}
