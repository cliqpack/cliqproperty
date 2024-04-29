<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contructs\UserContruct;
use App\Repositories\Repository\UserRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserContruct::class, UserRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
