<?php

namespace App\Mail;

use App\Models\ProfessorAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfessorAccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProfessorAccessRequest $accessRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'StudentLink — demande d\'accès professeur',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.professor-access-request',
            with: [
                'request' => $this->accessRequest,
            ],
        );
    }
}
