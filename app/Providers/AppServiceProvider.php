<?php

namespace App\Providers;

use App\Models\VpnClient;
use App\Observers\VpnClientObserver;
use App\Services\OpenVpn\DockerOpenVpnExecutorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DockerOpenVpnExecutorService::class, function () {
            return new DockerOpenVpnExecutorService(config('services.openvpn.docker_container'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VpnClient::observe(VpnClientObserver::class);
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        /*RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });*/

        RateLimiter::for('vpn-create', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()->id);
        });

        RateLimiter::for('vpn-provision', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()->id);
        });

        RateLimiter::for('vpn-revoke', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });
    }
}
