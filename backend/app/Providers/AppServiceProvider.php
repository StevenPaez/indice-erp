<?php

namespace App\Providers;

use App\Enums\AuditEvent;
use App\Enums\Capability;
use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use App\Policies\UserPolicy;
use App\Services\AuditService;
use App\Services\BookService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AuditService $auditService): void
    {
        Gate::policy(Book::class, BookPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        foreach (Capability::cases() as $capability) {
            Gate::define(
                $capability->value,
                fn (User $user): bool => $capability->isGrantedTo($user->role),
            );
        }
        Gate::after(function (User $user, string $ability, ?bool $result) use ($auditService): void {
            if ($result === false && request()->is('api/*')) {
                $auditService->record(
                    AuditEvent::AuthorizationDenied,
                    $user,
                    metadata: ['action' => request()->route()?->getName() ?? $ability],
                );
            }
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->rateLimitKey('login', $request));
        });

        RateLimiter::for('password-recovery', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->rateLimitKey('password-recovery', $request));
        });

        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim((string) config('app.frontend_url'), '/'),
                urlencode($token),
                urlencode($user->getEmailForPasswordReset()),
            );
        });
    }

    private function rateLimitKey(string $scope, Request $request): string
    {
        $email = Str::lower(trim((string) $request->input('email')));

        return hash('sha256', implode('|', [$scope, $email, $request->ip()]));
    }
}
