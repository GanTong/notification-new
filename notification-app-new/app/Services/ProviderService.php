<?php

namespace App\Services;

use App\Interfaces\VerificationInterface;
use App\Models\Providers\Local;
use App\Models\Providers\Twilio;
use Illuminate\Validation\ValidationException;

class ProviderService
{
    public function getProvider(string $providerName): VerificationInterface
    {
        if($providerName === 'Twilio') {
            return new Twilio();
        } else {
            return new Local();
        }

    }

}
