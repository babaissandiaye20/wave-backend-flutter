<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CarteMemberMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $utilisateur;
    public $compte;
    private $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct($utilisateur, $compte, $pdfPath)
    {
        $this->utilisateur = $utilisateur;
        $this->compte = $compte;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->to($this->utilisateur->login)
                    ->subject('Votre carte de membre')
                    ->view('emails.carte-membre')
                    ->attach(storage_path('app/public/' . $this->pdfPath), [
                        'as' => 'carte_membre.pdf',
                        'mime' => 'application/pdf'
                    ]);
    }
}