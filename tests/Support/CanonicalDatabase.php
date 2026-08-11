<?php

namespace Tests\Support;

use App\Models\Account;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

trait CanonicalDatabase
{
    protected function seedCanonicalDatabase(): void
    {
        Cache::flush();
        $this->assertSame(Command::SUCCESS, Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]));
    }

    protected function account(string $username): Account
    {
        return Account::query()->where('username', $username)->firstOrFail();
    }

    protected function studentFor(Account $account): Student
    {
        return Student::query()->where('account_id', $account->getKey())->firstOrFail();
    }
}
