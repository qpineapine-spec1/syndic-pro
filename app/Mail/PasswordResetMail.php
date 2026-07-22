<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $newPassword)
    {
    }

    public function build()
    {
        return $this->subject('Nouveau mot de passe')
            ->view('emails.password-reset')
            ->with(['newPassword' => $this->newPassword]);
    }
}
