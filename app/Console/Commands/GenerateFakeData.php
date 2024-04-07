<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Faker\Factory as Faker;

class GenerateFakeData extends Command
{
    protected $signature = 'generate:fake {model}';
    protected $description = 'Generate a fake JSON post data example for a given model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $modelClass = "App\\Models\\" . $modelName;

        if (!class_exists($modelClass)) {
            $this->error('Model does not exist.');
            return;
        }

        $model = new $modelClass;
        $attributes = $model->getFillable(); // Assuming you are using $fillable array in your model
        $faker = Faker::create();

        $fakeData = [];
        foreach ($attributes as $attribute) {
            $fakeData[$attribute] = $this->generateFakeDataForAttribute($faker, $attribute);
        }

        $this->info("Fake JSON for model {$modelName}:");
        $this->line(json_encode($fakeData, JSON_PRETTY_PRINT));
    }

    private function generateFakeDataForAttribute($faker, $attribute)
    {
        // Customize this method based on your specific model attributes or data types
        switch ($attribute) {
            case 'name':
            case 'username':
                return $faker->name;
            case 'email':
                return $faker->email;
            case 'phone':
            case 'phoneNumber':
                return $faker->phoneNumber;
            case 'address':
                return $faker->address;
            case 'date':
            case 'birthdate':
                return $faker->date;
            case 'text':
            case 'description':
                return $faker->text;
            default:
                return 'fake_' . $attribute;  // Default fake data
        }
    }
}