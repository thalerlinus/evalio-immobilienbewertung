<?php

namespace App\Mail;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $contact
     */
    public function __construct(
        public Offer $offer,
        public array $contact
    ) {
    }

    public function build(): self
    {
        $publicUrl = route('offers.public.show', $this->offer->view_token);

        return $this->subject(__('Ihre Angebotsbestätigung für Evalio Gutachten'))
            ->markdown('emails.offer.confirmed', [
                'offer' => $this->offer,
                'contact' => $this->contact,
                'publicUrl' => $publicUrl,
            ]);
    }
}
