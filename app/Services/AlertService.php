<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Signal;
use App\Models\StockPrice;
use App\Notifications\SignalAlertNotification;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * After signals are generated, check all active alerts and notify users.
     */
    public function checkAndFire(): void
    {
        $alerts = Alert::with(['user', 'stock'])
            ->where('is_active', true)
            ->where('is_triggered', false)
            ->get();

        foreach ($alerts as $alert) {
            try {
                $this->evaluate($alert);
            } catch (\Throwable $e) {
                Log::warning("AlertService: failed for alert #{$alert->id}: {$e->getMessage()}");
            }
        }
    }

    private function evaluate(Alert $alert): void
    {
        $latestPrice = $alert->stock->latestPrice;
        $latestSignal = $alert->stock->latestSignal;

        $triggered = match ($alert->condition_type) {
            'price_above' => $latestPrice && $latestPrice->close >= $alert->condition_value,
            'price_below' => $latestPrice && $latestPrice->close <= $alert->condition_value,
            'rsi_above'   => $latestSignal && $latestSignal->rsi_value >= $alert->condition_value,
            'rsi_below'   => $latestSignal && $latestSignal->rsi_value <= $alert->condition_value,
            'signal_buy'  => $latestSignal && $latestSignal->signal_type === 'BUY',
            'signal_sell' => $latestSignal && $latestSignal->signal_type === 'SELL',
            'volume_spike'=> $this->isVolumeSpike($alert->stock->id, $alert->condition_value ?? 1.5),
            default       => false,
        };

        if ($triggered && $latestSignal) {
            $alert->update([
                'is_triggered' => true,
                'triggered_at' => now(),
            ]);

            $alert->user->notify(new SignalAlertNotification($latestSignal, $alert));
            Log::info("Alert #{$alert->id} fired for {$alert->stock->symbol}");
        }
    }

    private function isVolumeSpike(int $stockId, float $multiplier): bool
    {
        $recent = StockPrice::where('stock_id', $stockId)
            ->orderByDesc('date')
            ->limit(11)
            ->get();

        if ($recent->count() < 11) {
            return false;
        }

        $latest  = $recent->first()->volume;
        $avgPrev = $recent->skip(1)->avg('volume');

        return $avgPrev > 0 && $latest >= $avgPrev * $multiplier;
    }
}
