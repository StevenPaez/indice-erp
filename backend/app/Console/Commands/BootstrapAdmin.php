<?php

namespace App\Console\Commands;

use App\Enums\AuditEvent;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class BootstrapAdmin extends Command
{
    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Create or promote the first active administrator';

    public function __construct(
        private readonly AuditService $auditService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->hasActiveAdministrator()) {
            $this->warn('An active administrator already exists. No changes were made.');

            return self::SUCCESS;
        }

        $email = Str::lower(trim((string) $this->ask('Administrator email')));

        if (! $this->validateEmail($email)) {
            return self::FAILURE;
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            $this->line(sprintf('Existing user: %s <%s>', $existingUser->name, $existingUser->email));

            if (! $this->confirm('Promote this user to administrator?')) {
                $this->warn('Bootstrap cancelled. No changes were made.');

                return self::SUCCESS;
            }

            return $this->promoteExistingUser($email);
        }

        $name = trim((string) $this->ask('Administrator name'));
        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        if (! $this->validateNewUser($name, $email, $password, $passwordConfirmation)) {
            return self::FAILURE;
        }

        return $this->createAdministrator($name, $email, $password);
    }

    private function promoteExistingUser(string $email): int
    {
        return $this->withBootstrapLock(function () use ($email): int {
            return DB::transaction(function () use ($email): int {
                if ($this->hasActiveAdministrator(lock: true)) {
                    $this->warn('Another administrator was created. No changes were made.');

                    return self::SUCCESS;
                }

                $user = User::query()
                    ->where('email', $email)
                    ->lockForUpdate()
                    ->firstOrFail();

                $user->role = UserRole::Admin;
                $user->is_active = true;
                $user->updated_by = null;
                $user->save();

                $this->auditService->record(
                    AuditEvent::AdminBootstrapped,
                    subject: $user,
                );

                $this->info('The first administrator was promoted successfully.');

                return self::SUCCESS;
            });
        });
    }

    private function createAdministrator(string $name, string $email, string $password): int
    {
        return $this->withBootstrapLock(function () use ($name, $email, $password): int {
            return DB::transaction(function () use ($name, $email, $password): int {
                if ($this->hasActiveAdministrator(lock: true)) {
                    $this->warn('Another administrator was created. No changes were made.');

                    return self::SUCCESS;
                }

                if (User::query()->where('email', $email)->lockForUpdate()->exists()) {
                    $this->error('The email was registered concurrently. Run the command again.');

                    return self::FAILURE;
                }

                $user = new User([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $user->role = UserRole::Admin;
                $user->is_active = true;
                $user->must_change_password = false;
                $user->created_by = null;
                $user->updated_by = null;
                $user->save();

                $this->auditService->record(
                    AuditEvent::AdminBootstrapped,
                    subject: $user,
                );

                $this->info('The first administrator was created successfully.');

                return self::SUCCESS;
            });
        });
    }

    private function withBootstrapLock(callable $callback): int
    {
        try {
            return Cache::lock('security:first-admin-bootstrap', 30)->block(5, $callback);
        } catch (LockTimeoutException) {
            $this->error('Another bootstrap operation is in progress. Try again.');

            return self::FAILURE;
        }
    }

    private function hasActiveAdministrator(bool $lock = false): bool
    {
        $query = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true);

        if ($lock) {
            return $query
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id'])
                ->isNotEmpty();
        }

        return $query->exists();
    }

    private function validateEmail(string $email): bool
    {
        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
        );

        return $this->reportValidationErrors($validator);
    }

    private function validateNewUser(
        string $name,
        string $email,
        string $password,
        string $passwordConfirmation,
    ): bool {
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(15), 'max:255'],
        ]);

        return $this->reportValidationErrors($validator);
    }

    private function reportValidationErrors(ValidatorContract $validator): bool
    {
        if ($validator->passes()) {
            return true;
        }

        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return false;
    }
}
