<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use Tests\TestCase;

class AccountActivationMailTest extends TestCase
{
    public function test_activation_email_uses_activate_route(): void
    {
        $mailable = new AccountActivationMail('test-token');

        $html = $mailable->render();

        $this->assertStringContainsString('/activate/test-token', $html);
        $this->assertStringNotContainsString('/activation/test-token', $html);
    }
}
