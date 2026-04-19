<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase.messaging', function ($app) {
            $credentialsPath = config('firebase.credentials');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
            }
            
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            
            return $factory->createMessaging();
        });
    }

    public function boot()
    {
        //
    }
}