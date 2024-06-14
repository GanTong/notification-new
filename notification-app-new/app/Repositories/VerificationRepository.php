<?php

namespace App\Repositories;

use App\Models\Verification;
use DateInterval;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VerificationRepository
{
    /** @var int */
    const PENDING = 0;

    /** @var int */
    const CONFIRM = 1;

    /** @var int */
    const CODE_LIFESPAN = 15;

    /**
     * @param string $channel
     * @param string $channelValue
     * @param string $code
     * @return Verification
     */
    public function storeCode(string $channel, string $channelValue, string $code): Verification
    {
        $verification = new Verification();

        $verification->channel = $channel;
        $verification->channel_value = $channelValue;
        $verification->code = $code;
        $verification->status = self::PENDING;

        $verification->save();

        return $verification->fresh();
    }

    /**
     * @param string $channelValue
     * @param string $channel
     * @param string $code
     * @return Model|Builder|object|null
     * @throws Exception
     */
    public function findValid(string $channelValue, string $channel, string $code)
    {
        if (isset($_COOKIE['set_verification_code_lifespan'])) {
            $lifespan = (int)$_COOKIE['set_verification_code_lifespan'];
        } else {
            $lifespan = self::CODE_LIFESPAN;
        }

        $datetime = new \DateTime('now');
        $createdAt = $datetime->sub(new DateInterval("PT" . $lifespan . "M"))->format('Y-m-d H:i:s');

        return DB::table('verifications')
            ->where('channel_value', $channelValue)
            ->where('channel', $channel)
            ->where('code', $code)
            ->where('created_at', '>', $createdAt)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * @param int $id
     * @return Model|Builder|object|null
     */
    public function findConfirmedById(int $id)
    {
        return DB::table('verifications')
        ->where('id', $id)
        ->where('status', self::CONFIRM)
        ->orderBy('created_at', 'desc')
        ->first();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function confirmCode(int $id)
    {
        $verification = new Verification();
        $verification = $verification->find($id);
        $verification->status = self::CONFIRM;
        $verification->confirm_at = Carbon::now();
        $verification->update();

        return $verification->fresh();
    }

    /**
     * @param string $channelValue
     * @param string $channel
     * @param string $code
     * @return Model|Builder|object|null
     */
    public function findConfirmed(string $channelValue, string $channel, string $code)
    {
        return DB::table('verifications')
            ->where('channel_value', $channelValue)
            ->where('channel', $channel)
            ->where('code', $code)
            ->where('status', self::CONFIRM)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * @return string
     */
    public function createCode(): string
    {
        return (string)rand(1000, 9999);
    }

}
