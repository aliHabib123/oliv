<?php

namespace App\Console\Commands;

use App\Services\Checkout\CheckoutSessionService;
use Illuminate\Console\Command;

class ExpireCheckoutSessionsCommand extends Command
{
    protected $signature = 'checkout:expire-sessions';

    protected $description = 'Expire checkout sessions that have been in payment_initiated state past their expiry time';

    public function handle(CheckoutSessionService $sessionService): int
    {
        $count = $sessionService->expireStale();

        $this->info("Expired {$count} checkout session(s).");

        return self::SUCCESS;
    }
}
