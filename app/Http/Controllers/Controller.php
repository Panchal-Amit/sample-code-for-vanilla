<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;


class Controller extends BaseController
{
    public function __construct()
    {
    }

    /**
     * Sending emails using UNOapp credentails.
     */
    protected function sendEmail($pdf_url, $to, $subject, $email_body = '')
    {
        // Send email
        try {
           $mail =  Mail::raw($email_body, function($message) use ($to, $subject, $pdf_url)
            {
                $message->from('info@unoapp.com', 'UNOapp');
            
                $message->to($to)->subject($subject);
                $message->bcc('bhavneet@unoapp.com');
                $message->attach($pdf_url, ['mime' => 'application/pdf']);
            });
                        
        } catch (Exception $err) {
            $error_message = 'Email Exception';
            Log::error($err);
        }
    }
}
