<?php

namespace App\Console\Commands;

use App\Models\SesiPembeli;
use Illuminate\Console\Command;

class CleanupSesiPembeli extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-sesi-pembeli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SesiPembeli::where('is_closed', true)
            ->where('expired_at', '<', now()->subDay())
            ->delete();
        return 0;
    }
}
