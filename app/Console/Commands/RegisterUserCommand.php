<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class RegisterUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:register {name} {email} {--token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register an user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $validator = $this->validator($this->arguments());

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $password = $this->secret('The password for the user?');
        $passwordConfirmation = $this->secret('Please confirm the password.');

        if ($password !== $passwordConfirmation) {
            return $this->error('The passwords didn\'t match! User not created!');
        }

        User::forceCreate([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($password),
            'api_token' => $token = str_random(60),
        ]);

        if($this->option('token')) {
            $this->info('The API token: ' . $token);
        }
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
        ]);
    }
}
