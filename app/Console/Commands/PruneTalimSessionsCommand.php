<?php

namespace App\Console\Commands;

use App\Support\TalimSession;
use Illuminate\Console\Command;

class PruneTalimSessionsCommand extends Command
{
    protected $signature = 'talim:prune-sessions';

    protected $description = 'Prune idle Talim guru tokens and expired web sessions';

    public function handle(): int
    {
        $tokens = TalimSession::pruneStaleGuruTokens();
        $sessions = TalimSession::pruneExpiredWebSessions();

        $this->info("Pruned {$tokens} idle Talim guru token(s) and {$sessions} expired web session(s).");

        return self::SUCCESS;
    }
}
