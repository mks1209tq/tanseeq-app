<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Authentication\Entities\User;
use Modules\Authorization\Entities\Role;

class AssignSuperAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-superadmin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign SuperAdmin role to a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return Command::FAILURE;
        }

        $superAdmin = Role::where('name', 'SuperAdmin')->first();

        if (! $superAdmin) {
            $this->error('SuperAdmin role not found. Please run the authorization seeder first.');

            return Command::FAILURE;
        }

        $user->roles()->syncWithoutDetaching([$superAdmin->id]);

        $this->info("SuperAdmin role assigned to {$user->email} ({$user->name})");

        return Command::SUCCESS;
    }
}
