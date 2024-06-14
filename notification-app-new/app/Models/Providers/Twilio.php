<?php

namespace App\Models\Providers;

use App\Interfaces\VerificationInterface;
use App\Libraries\RatelimiterLaravel;
use App\Repositories\VerificationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Twilio extends Model implements VerificationInterface
{
    const RATE_LIMITER_HIT = 100;
    const RATE_LIMITER_INTERVAL = 'hour';

    /**
     * @return string[]
     */
    public function getAvailableChannels(): array
    {
        return ['sms', 'whatsapp'];
    }

    /**
     * @param string $channel
     * @param string $destination
     * @return void
     * @throws ConfigurationException|TwilioException
     */
    public function sendCode(string $channel, string $destination): void
    {
        /*
         * Create random code
         */
        $verificationRepository = new VerificationRepository();
        $code = $verificationRepository->createCode();

        /*
         * Option to bypass twilio php sdk with cookie
         */
        if (!isset($_COOKIE['bypass_twilio_php'])) {
            $twilio = new Client($this->getAccountSID(), $this->getAuthToken());
            $twilio->messages
                ->create($destination,
                         array(
                             "from" => $this->getFromNumber(),
                             "body" => 'your code:' . $code
                         )
                );
        }

        /*
         * Store code to DB
         */
        $verificationRepository->storeCode($channel, $destination, $code);

    }

    /**
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
        return 'you have reached the rate limit, please try again in one '. $interval;
    }

    /**
     * @throws ValidationException
     */
    public function throttleDelivery(string $channel, string $destination): void
    {
        switch ($channel) {
            case 'sms':
            case 'whatsapp':
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
     * @return string
     */
    private function getAccountSID(): string
    {
        return (string)env('TWILIO_ACCOUNT_SID');
    }

    /**
     * @return string
     */
    private function getAuthToken(): string
    {
        return (string)env('TWILIO_AUTH_TOKEN');
    }

    /**
     * @return string
     */
    private function getServiceSID(): string
    {
        return (string)env('TWILIO_SERVICE_SID');
    }

    /**
     * @return string
     */
    private function getFromNumber(): string
    {
        return (string)env('TWILIO_FROM_NUMBER');
    }

}
