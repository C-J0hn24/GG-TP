<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $invoice
     */
    public function __construct(
        public string $recipientName,
        public array $invoice,
    ) {
    }

    public function envelope(): Envelope
    {
        $orderId = (string) ($this->invoice['order_id'] ?? '');

        return new Envelope(
            subject: 'GroceryGo Payment Successful - Invoice #'.$orderId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-payment-success',
        );
    }
}
