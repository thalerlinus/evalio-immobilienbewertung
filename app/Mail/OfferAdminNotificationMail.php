<?php

namespace App\Mail;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferAdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $contact
     */
    public function __construct(
        public Offer $offer,
        public array $contact,
        public array $billingContact
    ) {
    }

    public function build(): self
    {
        $publicUrl = route('offers.public.show', $this->offer->view_token);

        return $this->subject(__('Neues bestätigtes Angebot (Gutachten sinnvoll)'))
            ->markdown('emails.offer.admin-notification', [
                'offer' => $this->offer,
                'contact' => $this->contact,
                'billingContact' => $this->billingContact,
                'publicUrl' => $publicUrl,
            ]);
    }
}
