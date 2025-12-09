<?php

namespace Modules\Clipboard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Entities\User;
use Modules\Clipboard\Entities\ClipboardItem;

class AddSampleClipboardItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'a@a.com')->first();

        if (! $user) {
            $this->command->warn('User a@a.com not found. Skipping sample clipboard item creation.');
            return;
        }

        // Check if sample items already exist
        $existingCount = ClipboardItem::where('user_id', $user->id)->count();

        if ($existingCount > 0) {
            $this->command->info("User a@a.com already has {$existingCount} clipboard item(s).");
            return;
        }

        // Create sample clipboard items
        $samples = [
            [
                'title' => 'Welcome to Clipboard',
                'content' => 'This is your first clipboard item! You can copy and save multiple items here for quick access.',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'title' => 'Example URL',
                'content' => 'https://laravel.com',
                'type' => 'url',
                'order' => 2,
            ],
            [
                'title' => 'Sample Code Snippet',
                'content' => 'function greet(name) { return `Hello, ${name}!`; }',
                'type' => 'code',
                'order' => 3,
            ],
        ];

        foreach ($samples as $sample) {
            ClipboardItem::create([
                'user_id' => $user->id,
                'title' => $sample['title'],
                'content' => $sample['content'],
                'type' => $sample['type'],
                'order' => $sample['order'],
            ]);
        }

        $this->command->info("Created 3 sample clipboard items for user a@a.com (ID: {$user->id}).");
    }
}

