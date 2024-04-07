<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Faker\Factory as Faker;

class GenerateJsonExample extends Command
{
    protected $signature = 'generate:json-example {tableName}';
    protected $description = 'Generate a JSON example for inserting data into a table based on its migration file';

    public function handle()
    {
        $tableName = $this->argument('tableName');
        $migrationFiles = $this->getMigrationFiles($tableName);

        if (empty($migrationFiles)) {
            $this->error("No migration file found for the table '{$tableName}'.");
            return;
        }

        $columns = $this->parseMigrationFiles($migrationFiles);
        $jsonData = $this->generateJsonData($columns);

        $this->info("JSON Example for table '{$tableName}':");
        $this->line(json_encode($jsonData, JSON_PRETTY_PRINT));
    }

    protected function getMigrationFiles($tableName)
    {
        $finder = new Finder();
        $finder->files()->in(database_path('migrations'))->name('*create_' . $tableName . '_table*.php');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    protected function parseMigrationFiles($migrationFiles)
    {
        $columns = [];
        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/\$table->([\w]+)\(\'([\w]+)\'/', $content, $matches)) {
                foreach ($matches[2] as $index => $columnName) {
                    $columns[$columnName] = $matches[1][$index];
                }
            }
        }

        return $columns;
    }

    protected function generateJsonData($columns)
    {
        $faker = Faker::create();
        $jsonData = [];
        foreach ($columns as $column => $type) {
            $jsonData[$column] = $this->fakeDataForType($faker, $type);
        }

        return $jsonData;
    }

    protected function fakeDataForType($faker, $type)
    {
        switch ($type) {
            case 'string':
            case 'char':
                return $faker->words(3, true); // Generates a string of 3 words
            case 'text':
                return $faker->text(); // Generates a longer piece of text
            case 'bigint':
            case 'integer':
            case 'bigInteger':
            case 'mediumInteger':
            case 'smallInteger':
            case 'tinyInteger':
                return $faker->randomNumber(8, true); // Generates a large random number, true ensures it's not zero-prefixed
            case 'boolean':
                return $faker->boolean; // Generates a true or false
            case 'date':
                return $faker->date(); // Generates a date in 'Y-m-d' format
            case 'dateTime':
            case 'datetime': // Ensuring both possible uses are covered
            case 'timestamp':
                return $faker->dateTime()->format('Y-m-d H:i:s'); // Generates a date-time string
            default:
                return 'unknown'; // Default for unknown types
        }
    }
    
}