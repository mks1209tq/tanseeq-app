<?php

namespace Modules\Authentication\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authentication\Entities\AuthSetting;
use Modules\Authentication\Http\Requests\Admin\UpdateAuthSettingsRequest;
use Modules\Navigation\Attributes\NavigationItem;

class AuthSettingsController extends Controller
{
    /**
     * Display the settings form.
     */
    #[NavigationItem(label: 'Settings', icon: 'cog', order: 5, group: 'main')]
    public function edit(): View
    {
        // Get all settings or create defaults
        $allSettings = AuthSetting::all()->keyBy('key');

        // Ensure all settings exist with defaults
        $defaults = [
            'require_email_verification' => false,
            'force_two_factor' => false,
            'allow_registration' => true,
            'password_min_length' => 8,
            'session_lifetime' => 120,
            'max_login_attempts' => 5,
            'lockout_duration' => 60,
        ];

        $settings = [];
        foreach ($defaults as $key => $defaultValue) {
            if ($allSettings->has($key)) {
                $settings[$key] = $allSettings[$key];
            } else {
                // Create missing setting
                AuthSetting::set($key, $defaultValue, $this->getSettingDescription($key));
                $settings[$key] = AuthSetting::where('key', $key)->first();
            }
        }

        return view('authentication::admin.settings.edit', compact('settings'));
    }

    /**
     * Update the settings.
     */
    public function update(UpdateAuthSettingsRequest $request): RedirectResponse
    {
        // Define all boolean settings that should be handled
        $booleanSettings = [
            'require_email_verification',
            'force_two_factor',
            'allow_registration',
        ];

        // Handle boolean settings - check raw input, not validated
        // Unchecked checkboxes are not in the request at all
        foreach ($booleanSettings as $key) {
            $value = $request->has($key) && $request->input($key);
            AuthSetting::set($key, $value, $this->getSettingDescription($key));
        }

        // Handle other settings (integers) from validated data
        $validated = $request->validated();
        foreach ($validated as $key => $value) {
            if (! in_array($key, $booleanSettings) && $value !== null) {
                AuthSetting::set($key, $value, $this->getSettingDescription($key));
            }
        }

        return redirect()->route('authentication.settings.edit')
            ->with('status', 'Settings updated successfully.');
    }

    /**
     * Get the description for a setting key.
     */
    protected function getSettingDescription(string $key): ?string
    {
        return match ($key) {
            'require_email_verification' => 'Require users to verify their email address before accessing the application',
            'force_two_factor' => 'Force all users to enable two-factor authentication',
            'allow_registration' => 'Allow new users to register accounts',
            'password_min_length' => 'Minimum password length requirement',
            'session_lifetime' => 'Session lifetime in minutes',
            'max_login_attempts' => 'Maximum number of failed login attempts before lockout',
            'lockout_duration' => 'Duration in minutes that a user is locked out after exceeding max login attempts',
            default => null,
        };
    }
}
