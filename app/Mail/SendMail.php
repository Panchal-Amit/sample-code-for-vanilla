<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMail extends Mailable {

    use Queueable,
        SerializesModels;

    public $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function build() {

        $address = $this->data['email'];
        $subject = 'Forgot Password';
        $name = $this->data['first_name'] . ' ' . $this->data['last_name'];

        return $this->view('emails.sendForgotPassword')
                        ->from($address, $name)
                        ->replyTo($address, $name)
                        ->subject($subject);
    }

}
