<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ContactMessage $messageModel;

    public function __construct(ContactMessage $messageModel)
    {
        $this->messageModel = $messageModel;
        $this->subject('Nieuw contactbericht: ' . $messageModel->subject);
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name', 'Duidelijkheid.com Website'))
            ->replyTo($this->messageModel->email, $this->messageModel->name)
            ->view('emails.contact', [
                'm' => $this->messageModel,
            ]);
    }
}
