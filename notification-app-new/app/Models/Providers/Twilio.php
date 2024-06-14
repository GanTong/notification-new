<?php

namespace App\Models\Providers;

use App\Interfaces\VerificationInterface;
use App\Libraries\RatelimiterLaravel;
use App\Repositories\VerificationRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Twilio extends Model implements VerificationInterface
{
    const RATE_LIMITER_HIT = 100;
    const RATE_LIMITER_INTERVAL = 'hour';

    /** @var RatelimiterLaravel */
    protected $ratelimiter;

    /** @var Request */
    protected $request;

    /**
     * @var VerificationRepository
     */
    protected $verificationRepository;

    /**
     * @param RatelimiterLaravel $ratelimiter
     * @param Request $request
     * @param VerificationRepository $verificationRepository
     */
    public function __construct(RatelimiterLaravel $ratelimiter, Request $request, VerificationRepository $verificationRepository)
    {
        parent::__construct();
        $this->ratelimiter = $ratelimiter;
        $this->request = $request;
        $this->verificationRepository = $verificationRepository;
    }

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
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function sendCode(string $channel, string $destination): void
    {
        $twilio = new Client($this->getAccountSID(), $this->getAuthToken());

        $twilio->verify->v2->services($this->getServiceSID())->verifications->create($destination, $channel);

        $this->verificationRepository->createCode($channel, $destination);
    }

    /**
     * @throws ValidationException
     * @throws ConfigurationException
     */
    public function verifyCode(string $code, string $channel, string $destination): void
    {
        $twilio = new Client($this->getAccountSID(), $this->getAuthToken());

        try {
            $check = $twilio->verify->v2->services($this->getServiceSID())->verificationChecks->create($code, ["to" => $destination]);
        } catch (TwilioException $e) {
            throw ValidationException::withMessages(['exception1' => 'Could not verify code']);
        }

        if ($check->status != 'approved') {
            throw ValidationException::withMessages(['exception2' => 'Provided code is incorrect or already expired']);
        }

        $verification = $this->verificationRepository->findPending($destination, $channel);

        /*
         * If is not found, throw error
         */
        if (!$verification) {
            throw ValidationException::withMessages(['exception3' => 'Provided code is incorrect or already expired']);
        }

        $this->verificationRepository->updateByCode($verification->id, $code);

        $this->verificationRepository->confirmCode($verification->id);
    }

    /**
     * @param string $code
     * @param string $channel
     * @param string $destination
     * @return bool
     */
    public function isCodeVerified(string $code, string $channel, string $destination): bool
    {
        return (bool)$this->verificationRepository->findConfirmed($destination, $channel, $code);
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
                $this->ratelimiter->throttle(
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



}
