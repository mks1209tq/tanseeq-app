<?php

namespace Modules\ConfigTransports\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\ConfigTransports\Entities\TransportRequest;

class ExportTransportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transports:export {number : The transport request number} 
                            {--path= : Custom output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a released transport request to JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $number = $this->argument('number');

        $request = TransportRequest::where('number', $number)->first();

        if (! $request) {
            $this->error("Transport request '{$number}' not found.");

            return Command::FAILURE;
        }

        if (! $request->canBeExported()) {
            $this->error("Transport request '{$number}' cannot be exported. Status: {$request->status}");

            return Command::FAILURE;
        }

        $this->info("Exporting transport request '{$number}'...");

        // Collapse multiple items per object into final state
        $items = $this->collapseItems($request);

        // Build export data
        $exportData = [
            'transport' => [
                'number' => $request->number,
                'type' => $request->type,
                'description' => $request->description,
                'source_environment' => $request->source_environment,
                'target_environments' => $request->target_environments,
                'created_by' => $request->created_by,
                'released_by' => $request->released_by,
                'released_at' => $request->released_at?->toIso8601String(),
            ],
            'items' => $items,
        ];

        // Determine output path
        $path = $this->option('path') ?? "transports/{$number}.json";
        $fullPath = storage_path("app/{$path}");

        // Ensure directory exists
        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write JSON file
        file_put_contents($fullPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Update status
        $request->update(['status' => 'exported']);

        $this->info("Transport exported successfully to: {$fullPath}");

        return Command::SUCCESS;
    }

    /**
     * Collapse multiple items per object into final effective state.
     */
    protected function collapseItems(TransportRequest $request): array
    {
        $items = $request->items()->orderBy('created_at')->get();
        $collapsed = [];

        // Group items by object_type and identifier
        foreach ($items as $item) {
            $key = $item->object_type.'::'.json_encode($item->identifier);

            // If we've seen this object before, update the entry
            // The last operation wins
            if (isset($collapsed[$key])) {
                // If the last operation was delete, and this is create/update, replace
                if ($collapsed[$key]['operation'] === 'delete' && in_array($item->operation, ['create', 'update'])) {
                    $collapsed[$key] = [
                        'object_type' => $item->object_type,
                        'identifier' => $item->identifier,
                        'operation' => $item->operation,
                        'payload' => $item->payload,
                        'meta' => $item->meta,
                    ];
                } elseif (in_array($item->operation, ['create', 'update'])) {
                    // Merge payloads for updates
                    $collapsed[$key]['operation'] = 'update';
                    $collapsed[$key]['payload'] = array_merge(
                        $collapsed[$key]['payload'] ?? [],
                        $item->payload ?? []
                    );
                    $collapsed[$key]['meta'] = $item->meta;
                } elseif ($item->operation === 'delete') {
                    // Delete overrides everything
                    $collapsed[$key] = [
                        'object_type' => $item->object_type,
                        'identifier' => $item->identifier,
                        'operation' => 'delete',
                        'payload' => null,
                        'meta' => $item->meta,
                    ];
                }
            } else {
                $collapsed[$key] = [
                    'object_type' => $item->object_type,
                    'identifier' => $item->identifier,
                    'operation' => $item->operation,
                    'payload' => $item->payload,
                    'meta' => $item->meta,
                ];
            }
        }

        return array_values($collapsed);
    }
}

