<?php

namespace App\Mail;

use App\Models\Verification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $code;
    /**
     * @var Verification
     */
    protected $verification;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Verification $verification)
    {
        $this->verification = $verification;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('mail.verify-code')
            ->subject('Test Email')
            ->with([
                'code' => $this->verification->code
            ]);
    }
}
