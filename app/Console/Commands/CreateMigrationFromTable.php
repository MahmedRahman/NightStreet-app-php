<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateMigrationFromTable extends Command
{
    protected $signature = 'make:migration-from-table {tableName}';
    protected $description = 'Create a migration file from an existing database table';

    public function handle()
    {
        $tableName = $this->argument('tableName');
        $schema = DB::getDoctrineSchemaManager()->listTableColumns($tableName);

        if (empty($schema)) {
            $this->error("No columns found for table: $tableName");
            return;
        }

        $className = 'Create' . Str::studly($tableName) . 'Table';
        $filename = date('Y_m_d_His') . '_create_' . $tableName . '_table.php';
        $path = database_path('migrations/' . $filename);

        $content = $this->generateMigrationContent($className, $tableName, $schema);
        File::put($path, $content);

        $this->info("Migration created successfully: $filename");
    }

    protected function generateMigrationContent($className, $tableName, $schema)
    {
        $up = '';
        foreach ($schema as $column) {
            $type = $column->getType()->getName();
            $up .= "\$table->$type('{$column->getName()}')";
            if (!$column->getNotnull()) {
                $up .= "->nullable()";
            }
            $up .= ";\n            ";
        }

        return "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nclass $className extends Migration\n{\n    public function up()\n    {\n        Schema::create('$tableName', function (Blueprint \$table) {\n            $up\n        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$tableName');\n    }\n}\n";
    }
}