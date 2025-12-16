<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailBody;
    public ?string $attachmentPath;
    public ?string $attachmentName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $emailSubject, string $emailBody, ?string $attachmentPath = null, ?string $attachmentName = null)
    {
        $this->emailSubject = $emailSubject;
        $this->emailBody = $emailBody;
        $this->attachmentPath = $attachmentPath;
        $this->attachmentName = $attachmentName;
        $this->subject($emailSubject);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Gebruik de gegevens uit je .env / config/mail.php
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        $mail = $this
            ->from($fromEmail, $fromName)
            ->replyTo($fromEmail, $fromName)
            ->view('mail.admin_contact_reply', [
                'body' => $this->emailBody,
                'attachmentName' => $this->attachmentName
            ]);

        if ($this->attachmentPath) {
            $options = [];
            if ($this->attachmentName) {
                $options['as'] = $this->attachmentName;
            }
            $mail->attach($this->attachmentPath, $options);
        }

        return $mail;
    }
}
