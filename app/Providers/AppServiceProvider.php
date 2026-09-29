<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // PENCEGAHAN GLOBAL: Jika session simulasi ada, paksa Carbon menggunakan tanggal tersebut
        if (Session::has('simulated_date')) {
            Carbon::setTestNow(Carbon::parse(Session::get('simulated_date')));
        } else {
            Carbon::setTestNow(null);
        }
    }
}