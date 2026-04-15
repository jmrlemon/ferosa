<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build(): self
    {
        $label = ucfirst(str_replace('_', ' ', $this->order->status));

        return $this->subject("Order {$this->order->order_number} is now: {$label} — Ferosa Landscaping")
            ->view('mail.order-status-updated');
    }
}
