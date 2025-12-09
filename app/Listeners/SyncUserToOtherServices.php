<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Events\UserRoleAssigned;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SyncUserToOtherServices
{
    /**
     * Handle user created events.
     */
    public function handleUserCreated(UserCreated $event): void
    {
        // In microservice mode, notify other services about new user
        if (config('services.authentication.mode') === 'microservice') {
            $this->notifyServices('user.created', [
                'user_id' => $event->userId,
                'name' => $event->name,
                'email' => $event->email,
            ]);
        }
    }

    /**
     * Handle user updated events.
     */
    public function handleUserUpdated(UserUpdated $event): void
    {
        // In microservice mode, notify other services about user updates
        if (config('services.authentication.mode') === 'microservice') {
            $this->notifyServices('user.updated', [
                'user_id' => $event->userId,
                'changes' => $event->changes,
            ]);
        }
    }

    /**
     * Handle user role assigned events.
     */
    public function handleUserRoleAssigned(UserRoleAssigned $event): void
    {
        // In microservice mode, notify other services about role assignments
        if (config('services.authentication.mode') === 'microservice') {
            $this->notifyServices('user.role.assigned', [
                'user_id' => $event->userId,
                'role_id' => $event->roleId,
                'role_name' => $event->roleName,
            ]);
        }
    }

    /**
     * Notify other services via webhook/event bus.
     */
    protected function notifyServices(string $event, array $data): void
    {
        // In a real microservice setup, this would use:
        // - Message queue (RabbitMQ, Redis, SQS)
        // - Event bus (Kafka, AWS EventBridge)
        // - Webhooks to subscribed services

        $services = config('services.subscribers', []);

        foreach ($services as $serviceUrl) {
            try {
                Http::timeout(5)
                    ->post("{$serviceUrl}/webhooks/events", [
                        'event' => $event,
                        'data' => $data,
                        'timestamp' => now()->toIso8601String(),
                    ]);
            } catch (\Exception $e) {
                Log::warning("Failed to notify service about event", [
                    'service' => $serviceUrl,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

