<?php

namespace App\Services;

use App\Interfaces\VerificationInterface;
use App\Models\Providers\Twilio;
use Illuminate\Validation\ValidationException;

class ProviderService
{
    /**
     * @var Twilio
     */
    protected $twilio;

    public function __construct(Twilio $twilio)
    {
        $this->twilio = $twilio;
    }

    public function getProvider($providerName): VerificationInterface
    {
        switch ($providerName) {
            case 'Twilio':
                return $this->twilio;
                break;
        }
    }

}
