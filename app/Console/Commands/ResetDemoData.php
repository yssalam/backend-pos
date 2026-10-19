<?php

namespace App\Console\Commands;

use App\Support\DemoDataStore;
use Illuminate\Console\Command;

class ResetDemoData extends Command
{
    protected $signature = 'demo:reset';
    protected $description = 'Reset local demo JSON data to the bundled sample data';

    public function handle(): int
    {
        DemoDataStore::reset();
        $this->info('Demo data reset to the bundled sample data.');
        return self::SUCCESS;
    }
}
