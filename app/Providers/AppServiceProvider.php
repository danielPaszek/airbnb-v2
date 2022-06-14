<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //disable mass assiment protection on all models
        Model::unguard();

        //store in db custom names not full classes
        Relation::enforceMorphMap([
            'office' => Office::class,
            'user' => User::class
        ]);
    }
}
