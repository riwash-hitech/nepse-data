<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SignalAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Signal $signal,
        private readonly Alert  $alert
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (in_array('mail', $this->alert->notification_channels ?? [])) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stock = $this->signal->stock;
        $type  = $this->signal->signal_type;
        $color = $type === 'BUY' ? 'success' : ($type === 'SELL' ? 'error' : 'info');

        return (new MailMessage)
            ->subject("NEPSE {$type} Signal — {$stock->symbol}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$type} signal detected for **{$stock->symbol}** ({$stock->name})")
            ->line("**Price:** NPR " . number_format($this->signal->price_at_signal, 2))
            ->line("**Confidence:** {$this->signal->confidence}%")
            ->line("**Entry Zone:** NPR " . number_format($this->signal->entry_min, 2) . " – " . number_format($this->signal->entry_max, 2))
            ->line("**Stop Loss:** NPR " . number_format($this->signal->stop_loss, 2))
            ->line("**Target 1:** NPR " . number_format($this->signal->target_1, 2))
            ->line("**Target 2:** NPR " . number_format($this->signal->target_2, 2))
            ->action("View Stock Analysis", url(route('stocks.show', $stock->symbol)))
            ->line("*Disclaimer: This is an automated signal. Always do your own research before trading.*");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'signal_id'    => $this->signal->id,
            'stock_symbol' => $this->signal->stock->symbol,
            'signal_type'  => $this->signal->signal_type,
            'confidence'   => $this->signal->confidence,
            'price'        => $this->signal->price_at_signal,
            'entry_min'    => $this->signal->entry_min,
            'entry_max'    => $this->signal->entry_max,
            'stop_loss'    => $this->signal->stop_loss,
            'target_1'     => $this->signal->target_1,
        ];
    }
}
