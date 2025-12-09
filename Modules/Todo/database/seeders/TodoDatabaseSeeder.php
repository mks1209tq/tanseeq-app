<?php

namespace Modules\Todo\Database\Seeders;

use Modules\Authentication\Entities\User;
use Illuminate\Database\Seeder;
use Modules\Todo\Entities\Todo;

class TodoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');

            return;
        }

        foreach ($users as $user) {
            // Create 25-30 todos per user
            $todoCount = rand(25, 30);

            // Mix of completed and pending (approximately 50/50)
            $completedCount = (int) ($todoCount * 0.5);
            $pendingCount = $todoCount - $completedCount;

            // Create completed todos
            Todo::factory($completedCount)
                ->for($user)
                ->completed()
                ->create();

            // Create pending todos
            Todo::factory($pendingCount)
                ->for($user)
                ->pending()
                ->create();

            // Ensure we have todos with all priority levels
            Todo::factory(5)
                ->for($user)
                ->highPriority()
                ->create();

            Todo::factory(5)
                ->for($user)
                ->mediumPriority()
                ->create();

            Todo::factory(5)
                ->for($user)
                ->lowPriority()
                ->create();

            // Create some overdue todos
            Todo::factory(3)
                ->for($user)
                ->overdue()
                ->create();

            // Create some due today
            Todo::factory(2)
                ->for($user)
                ->dueToday()
                ->create();

            // Create some due in the future
            Todo::factory(5)
                ->for($user)
                ->dueFuture()
                ->create();

            // Create some without due dates
            Todo::factory(5)
                ->for($user)
                ->state(['due_date' => null])
                ->create();

            // Create some with short descriptions
            Todo::factory(3)
                ->for($user)
                ->state(['description' => 'Short task description.'])
                ->create();

            // Create some with long descriptions
            Todo::factory(3)
                ->for($user)
                ->state(['description' => 'This is a longer description that provides more context about the task. It includes multiple sentences and details about what needs to be done, why it\'s important, and any relevant information that might be helpful.'])
                ->create();

            // Create some without descriptions
            Todo::factory(3)
                ->for($user)
                ->state(['description' => null])
                ->create();

            // Create some with short titles
            Todo::factory(2)
                ->for($user)
                ->state(['title' => 'Quick task'])
                ->create();

            // Create some with longer titles
            Todo::factory(2)
                ->for($user)
                ->state(['title' => 'This is a much longer todo title that demonstrates how the UI handles extended text content'])
                ->create();
        }

        $this->command->info('Todo seeder completed successfully!');
    }
}
