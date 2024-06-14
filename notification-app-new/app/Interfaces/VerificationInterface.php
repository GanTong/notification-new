<?php

namespace App\Interfaces;

interface VerificationInterface
{

    /**
     * Get supported channels
     *
     * @return array
     */
    public function getAvailableChannels(): array;

    /**
     * send verification code
     *
     * @param string $channel
     * @param string $destination
     * @return void
     */
    public function sendCode(string $channel, string $destination): void;

    /**
     * Verify token
     *
     * @param string $code
     * @param string $channel
     * @param string $destination
     * @return void
     */
    public function verifyCode(string $code, string $channel, string $destination): void;

    /**
     * Returns true if a code has been verified
     *
     * @param string $code
     * @param string $channel
     * @param string $destination
     * @return bool
     */
    public function isCodeVerified(string $code, string $channel, string $destination): bool;

    /**
     * Throttle delivery requests
     *
     * @param string $channel
     * @param string $destination
     * @return void
     */
    public function throttleDelivery(string $channel, string $destination): void;


}
