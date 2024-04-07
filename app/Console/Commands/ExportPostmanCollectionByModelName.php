<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class ExportPostmanCollectionByModelName extends Command
{
    protected $signature = 'export:postman-model {model : The name of the model to export routes for} {filename=postman_collection.json}';
    protected $description = 'Export routes to a Postman collection file, organized by model name.';

    public function handle()
    {
        $routes = Route::getRoutes();
        $modelName = $this->argument('model');
        $collection = [
            'info' => [
                'name' => "{$modelName} API",
                '_postman_id' => uniqid(),
                'description' => "Routes for the {$modelName} model",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $routesByModel = [];

        foreach ($routes as $route) {
            if ($route->action['uses'] instanceof Closure) {
                continue;
            }

            $model = $this->getModelFromClass($route->action['controller'] ?? '');
            if (!$model || $model !== $modelName) {
                continue;
            }

            $routesByModel[$model][] = $route;
        }

        foreach ($routesByModel as $model => $routes) {
            $folder = [
                'name' => $model,
                'item' => [],
            ];

            foreach ($routes as $route) {
                $method = $route->methods()[0]; // Taking the first HTTP method
                $uri = $route->uri();
                $item = [
                    'name' => $uri,
                    'request' => [
                        'method' => $method,
                        'header' => [
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/' . $uri,
                            'host' => ['{{base_url}}'],
                            'path' => array_values(array_filter(explode('/', $uri))), // Clean up path segments
                        ],
                    ],
                    'response' => [],
                ];

                $folder['item'][] = $item;
            }

            $collection['item'][] = $folder;
        }

        if (empty($collection['item'])) {
            $this->error("No routes found for the model: {$modelName}");
            return;
        }

        file_put_contents($this->argument('filename'), json_encode($collection, JSON_PRETTY_PRINT));
        $this->info("Postman collection for {$modelName} exported to: " . $this->argument('filename'));
    }

    private function getModelFromClass($controllerAction)
    {
        // Extract the controller class from the controller action
        $controller = explode('@', $controllerAction)[0];
        try {
            $reflectedController = new ReflectionClass($controller);
        } catch (\ReflectionException $e) {
            // If reflection fails, the class could not be found
            return null;
        }
        $controllerName = $reflectedController->getShortName();

        // Convention-based approach to derive model name from controller name
        $modelName = str_replace('Controller', '', $controllerName); // Remove 'Controller' suffix

        // Check if model exists, this is a simple existence check and might need adjustment
        if (class_exists("App\\Models\\$modelName")) {
            return $modelName;
        }

        return null;
    }
}