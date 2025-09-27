<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class DeleteExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:prune-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all expired Sanctum personal access tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = PersonalAccessToken::where('expires_at', '<', now())
            ->delete();

        $this->info("Deleted {$deletedCount} expired tokens.");

        return 0;
    }
}
