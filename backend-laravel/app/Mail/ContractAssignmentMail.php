<?php

namespace App\Mail;

use App\Models\Contract\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New contract ready for your review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-assignment',
            with: [
                'contract' => $this->contract,
                'portalUrl' => url('/contract-review'),
            ],
        );
    }
}
