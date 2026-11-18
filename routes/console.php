<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// php artisan relay:token dispatcher@example.com
Artisan::command('relay:token {email}', function (string $email) {
    $user = User::firstOrCreate(
        ['email' => $email],
        ['name' => strstr($email, '@', true), 'password' => bin2hex(random_bytes(16))]
    );

    $this->info('API token for '.$user->email.':');
    $this->line($user->createToken('cli')->plainTextToken);
})->purpose('Create an API token for a dispatcher');
