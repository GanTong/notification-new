<?php

namespace App\Libraries;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RatelimiterLaravel
{

    /**
     * @param int $hit
     * @param string $interval
     * @param string $message
     * @param int|null $decayRateForMins
     * @return void
     * @throws ValidationException
     */
    public function throttle(int $hit, string $interval, string $message, ?int $decayRateForMins = 1): void
    {
        if (!$this->isRateLimitNotBreached($hit, $interval, $decayRateForMins)) {
            throw ValidationException::withMessages(['exception' => $message]);
        }
    }

    /**
     * @param int $hit
     * @param string $interval
     * @param int|null $decayRateForMins
     * @return bool
     */
    public function isRateLimitNotBreached(int $hit, string $interval, ?int $decayRateForMins): bool
    {
        switch ($interval) {
            case 'minute':
                $executed = RateLimiter::attempt(
                    'send-message:',
                    $hit,
                    function() {
                    }
                );
                break;
            case 'minutes':
                $executed = RateLimiter::attempt(
                    'send-message:',
                    $hit,
                    function() {
                    },
                    // decay rate in seconds per minutes
                    $decayRateForMins*60,
                );
                break;
            case 'hour':
                $executed = RateLimiter::attempt(
                    'send-message:',
                    $hit,
                    function() {
                    },
                    // decay rate in seconds per hour
                    3600,
                );
                break;
            case 'day':
                $executed = RateLimiter::attempt(
                    'send-message:',
                    $hit,
                    function() {
                    },
                    // decay rate in seconds per day
                    86400,
                );
                break;
        }

        return $executed;
    }

}
