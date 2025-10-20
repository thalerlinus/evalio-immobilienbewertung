<?php

namespace App\Mail;

use App\Models\Calculation;
use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CalculationResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Calculation $calculation,
        public Offer $offer
    ) {
    }

    public function build(): self
    {
        return $this->subject(__('Ihre Evalio Immobilien-Einschätzung'))
            ->markdown('emails.calculation.result', [
                'calculation' => $this->calculation,
                'offer' => $this->offer,
                'publicUrl' => route('offers.public.show', $this->offer->view_token),
                'contactName' => $this->offer->customer?->name
                    ?? data_get($this->offer->input_snapshot, 'customer.name'),
            ]);
    }
}
