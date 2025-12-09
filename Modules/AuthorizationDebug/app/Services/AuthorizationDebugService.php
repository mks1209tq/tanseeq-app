<?php

namespace Modules\AuthorizationDebug\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authentication\Entities\User;
use Modules\AuthorizationDebug\Entities\AuthorizationFailure;

class AuthorizationDebugService
{
    /**
     * Get the last authorization failure for a user.
     *
     * @param  User|int  $user
     * @return AuthorizationFailure|null
     */
    public function getLastFailureForUser(User|int $user): ?AuthorizationFailure
    {
        $userId = $user instanceof User ? $user->id : $user;

        return AuthorizationFailure::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Get the last authorization failure for a user ID.
     *
     * @param  int  $userId
     * @return AuthorizationFailure|null
     */
    public function getLastFailureForUserId(int $userId): ?AuthorizationFailure
    {
        return $this->getLastFailureForUser($userId);
    }

    /**
     * Get recent authorization failures for a user.
     *
     * @param  User|int  $user
     * @param  int  $limit
     * @return Collection<int, AuthorizationFailure>
     */
    public function getFailuresForUser(User|int $user, int $limit = 10): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return AuthorizationFailure::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get authorization statistics for a user.
     *
     * @param  User|int  $user
     * @return array<string, mixed>
     */
    public function getStatisticsForUser(User|int $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query = AuthorizationFailure::where('user_id', $userId);

        // All time statistics
        $total = $query->count();
        $successes = (clone $query)->where('is_allowed', true)->count();
        $failures = (clone $query)->where('is_allowed', false)->count();
        $successRate = $total > 0 ? round(($successes / $total) * 100, 2) : 0;

        // Last 24 hours
        $last24Hours = (clone $query)->where('created_at', '>=', now()->subDay())->count();
        $last24HoursSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDay())->count();
        $last24HoursFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDay())->count();

        // Last 7 days
        $last7Days = (clone $query)->where('created_at', '>=', now()->subDays(7))->count();
        $last7DaysSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDays(7))->count();
        $last7DaysFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDays(7))->count();

        // Last 30 days
        $last30Days = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        $last30DaysSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDays(30))->count();
        $last30DaysFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'all_time' => [
                'total' => $total,
                'successes' => $successes,
                'failures' => $failures,
                'success_rate' => $successRate,
            ],
            'last_24_hours' => [
                'total' => $last24Hours,
                'successes' => $last24HoursSuccesses,
                'failures' => $last24HoursFailures,
                'success_rate' => $last24Hours > 0 ? round(($last24HoursSuccesses / $last24Hours) * 100, 2) : 0,
            ],
            'last_7_days' => [
                'total' => $last7Days,
                'successes' => $last7DaysSuccesses,
                'failures' => $last7DaysFailures,
                'success_rate' => $last7Days > 0 ? round(($last7DaysSuccesses / $last7Days) * 100, 2) : 0,
            ],
            'last_30_days' => [
                'total' => $last30Days,
                'successes' => $last30DaysSuccesses,
                'failures' => $last30DaysFailures,
                'success_rate' => $last30Days > 0 ? round(($last30DaysSuccesses / $last30Days) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get global authorization statistics (all users).
     *
     * @return array<string, mixed>
     */
    public function getGlobalStatistics(): array
    {
        $query = AuthorizationFailure::query();

        // All time statistics
        $total = $query->count();
        $successes = (clone $query)->where('is_allowed', true)->count();
        $failures = (clone $query)->where('is_allowed', false)->count();
        $successRate = $total > 0 ? round(($successes / $total) * 100, 2) : 0;

        // Last 24 hours
        $last24Hours = (clone $query)->where('created_at', '>=', now()->subDay())->count();
        $last24HoursSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDay())->count();
        $last24HoursFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDay())->count();

        // Last 7 days
        $last7Days = (clone $query)->where('created_at', '>=', now()->subDays(7))->count();
        $last7DaysSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDays(7))->count();
        $last7DaysFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDays(7))->count();

        // Last 30 days
        $last30Days = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        $last30DaysSuccesses = (clone $query)->where('is_allowed', true)->where('created_at', '>=', now()->subDays(30))->count();
        $last30DaysFailures = (clone $query)->where('is_allowed', false)->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'all_time' => [
                'total' => $total,
                'successes' => $successes,
                'failures' => $failures,
                'success_rate' => $successRate,
            ],
            'last_24_hours' => [
                'total' => $last24Hours,
                'successes' => $last24HoursSuccesses,
                'failures' => $last24HoursFailures,
                'success_rate' => $last24Hours > 0 ? round(($last24HoursSuccesses / $last24Hours) * 100, 2) : 0,
            ],
            'last_7_days' => [
                'total' => $last7Days,
                'successes' => $last7DaysSuccesses,
                'failures' => $last7DaysFailures,
                'success_rate' => $last7Days > 0 ? round(($last7DaysSuccesses / $last7Days) * 100, 2) : 0,
            ],
            'last_30_days' => [
                'total' => $last30Days,
                'successes' => $last30DaysSuccesses,
                'failures' => $last30DaysFailures,
                'success_rate' => $last30Days > 0 ? round(($last30DaysSuccesses / $last30Days) * 100, 2) : 0,
            ],
        ];
    }
}

