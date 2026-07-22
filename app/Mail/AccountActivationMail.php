<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountActivationMail extends Mailable
{
    public function __construct(public string $token)
    {
    }

    public function build()
    {
        return $this->subject('Activation de votre compte')
            ->view('emails.activation')
            ->with(['token' => $this->token]);
    }
}
