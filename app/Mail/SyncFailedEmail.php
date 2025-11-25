<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SyncFailedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $errorMessage;
    public $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $errorMessage)
    {
        $this->order = $order;
        $this->errorMessage = $errorMessage;

        $domain = config('app.domain', 'localhost');
        $subdomain = $order->tenant ? $order->tenant->subdomain : 'admin';
        $this->dashboardUrl = "http://{$subdomain}.{$domain}/dashboard/orders";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sync Failed Notification - Order #' . $this->order->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sync_failed',
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
