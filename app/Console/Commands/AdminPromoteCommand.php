<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class AdminPromoteCommand extends Command
{
    protected $signature = 'admin:promote {email : Email of the account to grant administrator role}';

    protected $description = 'Grant the administrator role to an existing user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            info("[{$email}] is already an administrator.");

            return self::SUCCESS;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();

        info("[{$email}] is now an administrator.");

        return self::SUCCESS;
    }
}
