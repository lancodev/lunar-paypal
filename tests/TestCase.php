<?php

namespace Lancodev\LunarPaypal\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lancodev\LunarPaypal\LunarPaypalServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Lancodev\\LunarPaypal\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LunarPaypalServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_lunar-paypal_table.php.stub';
        $migration->up();
        */
    }
}
