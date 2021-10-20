<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDemoMail extends Mailable {

    use Queueable,
        SerializesModels;

    public $data;

    public function __construct($message) {        
        $this->data = $message;
    }

    public function build() {

        $address = 's2s-alert@dealer-fx.com';
        $subject = "Manager Requested - PreDelivery";        

        return $this->view('emails.sendDemoMail')
                        ->from($address)
                        ->replyTo($address)                        
                        ->subject($subject);
    }

}
