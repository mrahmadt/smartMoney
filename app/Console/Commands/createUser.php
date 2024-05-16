<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class createUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:createUser {--name= : Name of the newly created user.} {--email= : E-Mail of the newly created user.}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually creates a new laravel user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
                // Enter username, if not present via command line option
                $name = $this->option('name');
                if ($name === null) {
                    $name = $this->ask('Please enter your name.');
                }
        
                // Enter email, if not present via command line option
                $email = $this->option('email');
                if ($email === null) {
                    $email = $this->ask('Please enter your E-Mail.');
                }
        
                // Always enter password from userinput for more security.
                $password = $this->secret('Please enter a new password.');
                $password_confirmation = $this->secret('Please confirm the password');
        
                if($password != $password_confirmation){
                    $this->error('Passwords do not match');
                    return;
                }
                // Prepare input for the fortify user creation action
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($password),
                ];
                $password = Hash::make('secret');
                try {
                    $user = User::create($data);
                }
                catch (\Exception $e) {
                    $this->error($e->getMessage());
                    return;
                }
        
                // Success message
                $this->info('User created successfully!');
                $this->info('New user id: ' . $user->id);
    }
}
