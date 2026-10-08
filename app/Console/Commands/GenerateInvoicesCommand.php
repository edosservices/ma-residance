<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateInvoicesCommand extends Command
{
    protected $signature = 'rent:generate';

    protected $description = 'Active les contrats dus et émet les factures de loyer';

    public function handle(BillingService $billing): int
    {
        $billing->generateDue();
        $this->info('Factures de loyer vérifiées.');

        return self::SUCCESS;
    }
}
