<?php

namespace Modules\Authentication\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Entities\AuthSetting;

class AuthSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default settings
        AuthSetting::set('require_email_verification', false, 'boolean', 'Require users to verify their email address before accessing the application');
        AuthSetting::set('force_two_factor', false, 'boolean', 'Force all users to enable two-factor authentication');
        AuthSetting::set('allow_registration', true, 'boolean', 'Allow new users to register accounts');
        AuthSetting::set('password_min_length', 8, 'integer', 'Minimum password length requirement');
        AuthSetting::set('session_lifetime', 120, 'integer', 'Session lifetime in minutes');
        AuthSetting::set('max_login_attempts', 5, 'integer', 'Maximum number of failed login attempts before lockout');
        AuthSetting::set('lockout_duration', 60, 'integer', 'Duration in minutes that a user is locked out after exceeding max login attempts');

        if ($this->command) {
            $this->command->info('Auth settings seeded successfully!');
        }
    }
}

