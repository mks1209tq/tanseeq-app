<?php

namespace Modules\ConfigTransports\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\ConfigTransports\Contracts\Transportable;
use Modules\ConfigTransports\Entities\TransportImportLog;

class ImportTransportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transports:import {path : Path to the transport JSON file}
                            {--force : Force import even in dev environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a transport request from JSON file';

    /**
     * Registry of transportable handlers.
     *
     * @var array<string, class-string<Transportable>>
     */
    protected array $handlers = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Transport file not found: {$path}");

            return Command::FAILURE;
        }

        // Check environment
        $environmentRole = config('system.environment_role', 'dev');

        if ($environmentRole === 'dev' && ! $this->option('force')) {
            $this->error('Imports are not allowed in DEV environment. Use --force to override.');

            return Command::FAILURE;
        }

        $this->info("Reading transport file: {$path}");

        $data = json_decode(file_get_contents($path), true);

        if (! $data || ! isset($data['transport'], $data['items'])) {
            $this->error('Invalid transport file format.');

            return Command::FAILURE;
        }

        $transport = $data['transport'];
        $items = $data['items'];

        $this->info("Importing transport '{$transport['number']}' with ".count($items).' items...');

        // Resolve dependencies and sort items
        $sortedItems = $this->resolveDependencies($items);

        if (empty($sortedItems)) {
            $this->error('Failed to resolve dependencies. Circular dependency detected.');

            return Command::FAILURE;
        }

        // Import items
        $summary = [
            'total' => count($sortedItems),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($sortedItems as $item) {
                $result = $this->importItem($item, $summary);

                if ($result === 'success') {
                    $summary['success']++;
                    $this->line("  ✓ {$item['object_type']} ({$this->formatIdentifier($item['identifier'])})");
                } elseif ($result === 'skipped') {
                    $summary['skipped']++;
                    $this->line("  ⊘ {$item['object_type']} ({$this->formatIdentifier($item['identifier'])}) - skipped");
                } else {
                    $summary['failed']++;
                    $this->error("  ✗ {$item['object_type']} ({$this->formatIdentifier($item['identifier'])}) - {$result}");
                    $summary['errors'][] = [
                        'object_type' => $item['object_type'],
                        'identifier' => $item['identifier'],
                        'error' => $result,
                    ];
                }
            }

            DB::commit();

            // Determine status
            $status = 'success';
            if ($summary['failed'] > 0) {
                $status = $summary['success'] > 0 ? 'partial' : 'failed';
            }

            // Create import log
            TransportImportLog::create([
                'transport_number' => $transport['number'],
                'import_environment' => $environmentRole,
                'imported_by' => auth()->id(),
                'status' => $status,
                'summary' => $summary,
            ]);

            $this->newLine();
            $this->info("Import completed: {$summary['success']} succeeded, {$summary['skipped']} skipped, {$summary['failed']} failed");

            return $status === 'failed' ? Command::FAILURE : Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("Import failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Resolve dependencies and return items in import order.
     */
    protected function resolveDependencies(array $items): array
    {
        // Build dependency graph
        $graph = [];
        $itemMap = [];

        foreach ($items as $index => $item) {
            $key = $item['object_type'].'::'.json_encode($item['identifier']);
            $itemMap[$key] = $item;
            $graph[$key] = [];

            // Get dependencies for this item
            $handler = $this->getHandler($item['object_type']);

            if ($handler) {
                // Create a temporary model instance to get dependencies
                // For now, we'll use a simplified approach
                // In a real implementation, you might need to instantiate the model
                // or have a static method to get dependencies from payload
            }
        }

        // Topological sort
        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach ($graph as $node => $deps) {
            if (! isset($visited[$node])) {
                if (! $this->topologicalSort($node, $graph, $visited, $visiting, $sorted, $itemMap)) {
                    return []; // Circular dependency
                }
            }
        }

        return array_reverse($sorted);
    }

    /**
     * Topological sort helper.
     */
    protected function topologicalSort(
        string $node,
        array $graph,
        array &$visited,
        array &$visiting,
        array &$sorted,
        array $itemMap
    ): bool {
        if (isset($visiting[$node])) {
            return false; // Circular dependency
        }

        if (isset($visited[$node])) {
            return true;
        }

        $visiting[$node] = true;

        // Process dependencies
        foreach ($graph[$node] ?? [] as $dep) {
            if (! $this->topologicalSort($dep, $graph, $visited, $visiting, $sorted, $itemMap)) {
                return false;
            }
        }

        unset($visiting[$node]);
        $visited[$node] = true;
        $sorted[] = $itemMap[$node];

        return true;
    }

    /**
     * Import a single item.
     */
    protected function importItem(array $item, array &$summary): string
    {
        $handler = $this->getHandler($item['object_type']);

        if (! $handler) {
            return "Handler not found for object type: {$item['object_type']}";
        }

        try {
            $identifier = $item['identifier'];
            $payload = $item['payload'] ?? [];
            $operation = $item['operation'];

            // Check if object exists
            $exists = $this->objectExists($handler, $identifier);

            if ($exists && $operation === 'create') {
                // Handle conflict
                $strategy = $this->getConflictStrategy($item['object_type']);

                if ($strategy === 'skip') {
                    return 'skipped';
                } elseif ($strategy === 'fail') {
                    return 'Object already exists';
                }
                // 'update' strategy: change operation to update
                $operation = 'update';
            }

            $handler::applyTransportPayload($identifier, $payload, $operation);

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get handler class for object type.
     */
    protected function getHandler(string $objectType): ?string
    {
        if (isset($this->handlers[$objectType])) {
            return $this->handlers[$objectType];
        }

        // Try to find handler by convention
        // This is a simplified approach - in production, you'd have a registry
        $possibleClasses = [
            "Modules\\Authorization\\Entities\\".ucfirst(str_replace('_', '', ucwords($objectType, '_'))),
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class) && is_subclass_of($class, Transportable::class)) {
                $this->handlers[$objectType] = $class;

                return $class;
            }
        }

        return null;
    }

    /**
     * Check if an object exists.
     */
    protected function objectExists(string $handler, array|string $identifier): bool
    {
        // This is a simplified check - in production, you'd need to implement
        // a proper lookup based on the identifier
        return false;
    }

    /**
     * Get conflict resolution strategy for object type.
     */
    protected function getConflictStrategy(string $objectType): string
    {
        // Default strategy from config
        return config('system.default_conflict_resolution', 'update');
    }

    /**
     * Format identifier for display.
     */
    protected function formatIdentifier(array|string $identifier): string
    {
        if (is_array($identifier)) {
            return json_encode($identifier);
        }

        return $identifier;
    }
}

