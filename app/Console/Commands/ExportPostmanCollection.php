<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class ExportPostmanCollection extends Command
{
    protected $signature = 'export:postman {filename=postman_collection.json}';
    protected $description = 'Export routes to a Postman collection file, organized by controller.';

    // Manual mapping of models to their fields for POST requests
    protected $modelFieldsMapping = [
        // Example: \App\Models\User::class => ['name' => '', 'email' => '', 'password' => ''],
        // Add your own model mappings here
    ];

    public function handle()
    {
        $routes = Route::getRoutes();
        $collection = [
            'info' => [
                'name' => 'Laravel API',
                '_postman_id' => uniqid(),
                'description' => '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $routesByController = [];

        foreach ($routes as $route) {
            if ($route->action['uses'] instanceof Closure) {
                continue;
            }

            $controllerAction = explode('@', $route->action['uses'] ?? '');
            $controller = $controllerAction[0] ?? 'Closure';
            $routesByController[$controller][] = $route;
        }

        foreach ($routesByController as $controller => $routes) {
            $folder = [
                'name' => class_basename($controller),
                'item' => [],
            ];

            foreach ($routes as $route) {
                $method = strtolower($route->methods()[0]);
                $actionModelFields = $this->getModelFieldsForAction($controller, $method);

                $item = [
                    'name' => $route->uri(),
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => [
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                            ],
                        ],
                        'body' => [],
                        'url' => [
                            'raw' => '{{base_url}}/' . $route->uri(),
                            'host' => ['{{base_url}}'],
                            'path' => explode('/', $route->uri()),
                        ],
                        'response' => [],
                    ],
                ];

                if ($method === 'post' && !empty($actionModelFields)) {
                    $item['request']['body'] = [
                        'mode' => 'raw',
                        'raw' => json_encode($actionModelFields, JSON_PRETTY_PRINT),
                    ];
                }

                $folder['item'][] = $item;
            }

            $collection['item'][] = $folder;
        }

        file_put_contents($this->argument('filename'), json_encode($collection, JSON_PRETTY_PRINT));
        $this->info("Postman collection exported to: " . $this->argument('filename'));
    }

    private function getModelFieldsForAction($controller, $method)
    {
        // Example approach to derive model from controller and method
        // This could be replaced with more sophisticated logic to dynamically derive model and its fields
        $modelClass = $this->getModelFromClass($controller);
        if (!$modelClass || !array_key_exists($modelClass, $this->modelFieldsMapping)) {
            return [];
        }

        return $this->modelFieldsMapping[$modelClass];
    }

    private function getModelFromClass($controller)
    {
        // Implement logic to derive model class name from controller if possible
        // This might involve naming conventions or other application-specific logic
        return null; // Placeholder implementation
    }
}