<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class GenerateCrudFromTable extends Command
{
    protected $signature = 'make:crud:table {tableName}';
    protected $description = 'Generate CRUD operations from a table schema.';

    public function handle()
    {
        $tableName = $this->argument('tableName');
        if (!Schema::hasTable($tableName)) {
            $this->error("The table '$tableName' does not exist.");
            return;
        }

        $columns = Schema::getColumnListing($tableName);
        // Example for generating a model with fillable attributes
        $this->generateModel($tableName, $columns);


        // Further steps would include generating controllers, resources, and routes
    }

    protected function generateModel($tableName, $columns)
    {
        $modelName = Str::studly(Str::singular($tableName));
        $fillable = implode("', '", $columns);

        // Define columns to hide
        $hidden = implode("', '", ['id', 'created_at', 'updated_at']);

        $modelTemplate = "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass $modelName extends Model\n{\n    protected \$fillable = ['$fillable'];\n\n    protected \$hidden = ['$hidden'];\n}";

        // Write the model file to the appropriate directory
        file_put_contents(app_path("/Models/{$modelName}.php"), $modelTemplate);
        $this->info("Model $modelName generated successfully.");
        $this->generateResourceCollection($modelName);
        $this->generateResource($modelName);
        $this->generateController($modelName);
        $this->appendRoutesToApiFile($modelName);
    }


    protected function generateController($modelName)
    {
        $controllerName = "{$modelName}Controller";
        $modelVariable = Str::camel($modelName);
        $modelPlural = Str::plural($modelVariable);
        $resourceName = "{$modelName}Resource";
        $resourceCollectionName = "{$modelName}Collection";

        $controllerTemplate = <<<EOT
    <?php
    
    namespace App\Http\Controllers;
    
    use Illuminate\Http\Request;
    use App\Models\\$modelName;
    use App\Http\Resources\\$resourceName;
    use App\Http\Resources\\$resourceCollectionName as {$modelName}ResourceCollection;
    use Illuminate\Support\Facades\Validator;
    
    class $controllerName extends Controller
    {
        // Display a listing of the resource.
        public function index()
        {
            \$${modelPlural} = $modelName::all();
            return new {$modelName}ResourceCollection(\$${modelPlural});
        }
    
        // Store a newly created resource in storage.
        public function store(Request \$request)
        {
            \$validator = Validator::make(\$request->all(), [
                // 'name' => 'required|string|max:255',
                // Add validation rules
            ]);
    
            if (\$validator->fails()) {
                return response()->json(\$validator->errors(), 400);
            }
    
            \$${modelVariable} = $modelName::create(\$request->all());
            return new $resourceName(\$${modelVariable});
        }
    
        // Display the specified resource.
        public function show(\$id)
        {
            \$${modelVariable} = $modelName::find(\$id);
    
            if (!\$${modelVariable}) {
                return response()->json(['message' => 'Not found'], 404);
            }
    
            return new $resourceName(\$${modelVariable});
        }
    
        // Update the specified resource in storage.
        public function update(Request \$request, \$id)
        {
            \$${modelVariable} = $modelName::find(\$id);
    
            if (!\$${modelVariable}) {
                return response()->json(['message' => 'Not found'], 404);
            }
    
            \$validator = Validator::make(\$request->all(), [
                // 'name' => 'required|string|max:255',
                // Add validation rules
            ]);
    
            if (\$validator->fails()) {
                return response()->json(\$validator->errors(), 400);
            }
    
            \$${modelVariable}->update(\$request->all());
            return new $resourceName(\$${modelVariable});
        }
    
        // Remove the specified resource from storage.
        public function destroy(\$id)
        {
            \$${modelVariable} = $modelName::find(\$id);
    
            if (!\$${modelVariable}) {
                return response()->json(['message' => 'Not found'], 404);
            }
    
            \$${modelVariable}->delete();
            return response()->json(['message' => 'Deleted successfully'], 200);
        }
    }
    EOT;

        // Ensure the Controllers directory exists
        if (!is_dir(app_path('Http/Controllers'))) {
            mkdir(app_path('Http/Controllers'), 0755, true);
        }

        // Write the controller file
        file_put_contents(app_path("Http/Controllers/{$controllerName}.php"), $controllerTemplate);
        $this->info("$controllerName generated successfully.");
    }



    protected function appendRoutesToApiFile($modelName)
    {
        $modelPlural = Str::plural(Str::snake($modelName));
        $controllerName = "{$modelName}Controller";

        $routeDefinition = "Route::apiResource('" . $modelPlural . "', " . $controllerName . "::class);" . PHP_EOL;

        // Ensure there's a newline at the end of the file before appending
        file_put_contents(base_path('routes/api.php'), $routeDefinition, FILE_APPEND | LOCK_EX);

        $this->info("Routes for $modelName appended to routes/api.php successfully.");
    }


    protected function generateResourceCollection($modelName)
    {
        $resourceCollectionName = "{$modelName}Collection";

        Artisan::call('make:resource', [
            'name' => "{$resourceCollectionName}",
            '--collection' => true,
        ]);

        $this->info("Resource Collection $resourceCollectionName generated successfully.");
    }


    protected function generateResource($modelName)
    {
        $resourceName = "{$modelName}Resource";

        Artisan::call('make:resource', [
            'name' => $resourceName
        ]);

        $this->info("Eloquent Resource $resourceName generated successfully.");
    }


}