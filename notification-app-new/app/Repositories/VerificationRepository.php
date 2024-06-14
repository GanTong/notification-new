<?php

namespace App\Repositories;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VerificationRepository
{
    /** @var int */
    const PENDING = 0;

    /** @var int */
    const CONFIRM = 1;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Verification
     */
    private $verification;

    /**
     * @param Request $request
     * @param Verification $verification
     */
    public function __construct(Request $request, Verification $verification)
    {
        $this->request = $request;
        $this->verification = $verification;
    }

    /**
     * @param string $channel
     * @param string $channelValue
     * @param string|null $token
     * @param string|null $code
     * @return Verification
     */
    public function createCode(string $channel, string $channelValue, ?string $token = null, ?string $code = null): Verification
    {
        $verification = new $this->verification();

        $verification->channel = $channel;
        $verification->channel_value = $channelValue;
        $verification->code = $code;
        $verification->status = self::PENDING;
        $verification->ip = $this->request->ip();
        $verification->token = $token ?? null;

        $verification->save();

        return $verification->fresh();
    }

    /**
     * @param string $channelValue
     * @param string $channel
     * @param string|null $code
     * @return Model|Builder|object|null
     */
    public function findPending(string $channelValue, string $channel, ?string $code = null)
    {
        return DB::table('verifications')
            ->where('channel_value', $channelValue)
            ->where('channel', $channel)
            ->where('code', $code)
            ->where('status', self::PENDING)
            ->first();
    }

    /**
     * @param int $id
     * @param string $code
     * @return mixed
     */
    public function updateByCode(int $id, string $code)
    {
        $verification = $this->verification->find($id);
        $verification->code = $code;
        $verification->update();

        return $verification->fresh();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function confirmCode(int $id)
    {
        $verification = $this->verification->find($id);
        $verification->status = self::CONFIRM;
        $verification->confirm_at = Carbon::now();
        $verification->update();

        return $verification->fresh();
    }

    public function findConfirmed(string $channelValue, string $channel, string $code)
    {
        return DB::table('verifications')
            ->where('channel_value', $channelValue)
            ->where('channel', $channel)
            ->where('code', $code)
            ->where('status', self::CONFIRM)
            ->first();
    }

}
