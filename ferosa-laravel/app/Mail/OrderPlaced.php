<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build(): self
    {
        return $this->subject('We received order '.$this->order->order_number.' — Ferosa Landscaping')
            ->view('mail.order-placed');
    }
}
