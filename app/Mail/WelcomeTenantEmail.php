<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeTenantEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $user;
    public $password;
    public $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Tenant $tenant, User $user, string $password)
    {
        $this->tenant = $tenant;
        $this->user = $user;
        $this->password = $password;

        $domain = config('app.domain', 'localhost');
        $this->dashboardUrl = "http://{$tenant->subdomain}.{$domain}/dashboard";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome_tenant',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
