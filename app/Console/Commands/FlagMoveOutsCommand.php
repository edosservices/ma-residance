<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MoveOutService;
use Illuminate\Console\Command;

class FlagMoveOutsCommand extends Command
{
    protected $signature = 'rent:move-outs';

    protected $description = 'Signale les départs proches';

    public function handle(MoveOutService $moveOuts): int
    {
        $moveOuts->flagUpcoming();
        $this->info('Départs proches vérifiés.');

        return self::SUCCESS;
    }
}
