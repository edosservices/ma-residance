<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class SendRentRemindersCommand extends Command
{
    protected $signature = 'rent:remind';

    protected $description = 'Envoie les rappels de loyer configurés';

    public function handle(BillingService $billing): int
    {
        $billing->sendReminders();
        $this->info('Rappels traités.');

        return self::SUCCESS;
    }
}
