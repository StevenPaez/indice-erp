<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\SessionResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (
            $user === null
            || ! Hash::check($credentials['password'], $user->password)
            || ! $user->is_active
        ) {
            $this->auditService->record(
                AuditEvent::LoginFailed,
                subject: $user,
            );

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->auditService->record(
            AuditEvent::LoginSucceeded,
            $user,
            $user,
        );

        return response()->json(SessionResource::make($user)->resolve($request));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->auditService->record(
            AuditEvent::Logout,
            $user,
            $user,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        Auth::guard('web')->logoutOtherDevices($validated['current_password']);

        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->updated_by = $user->getKey();
        $user->save();

        $this->auditService->record(
            AuditEvent::UserPasswordChanged,
            $user,
            $user,
        );

        return response()->json(SessionResource::make($user)->resolve($request));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        Password::sendResetLink(['email' => $email]);

        $this->auditService->record(
            AuditEvent::PasswordResetRequested,
            subject: $user,
        );

        return response()->json([
            'message' => 'Si la cuenta existe, recibirás instrucciones para restablecer la contraseña.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->password = Hash::make($password);
                $user->must_change_password = false;
                $user->updated_by = $user->getKey();
                $user->setRememberToken(Str::random(60));
                $user->save();

                $this->auditService->record(
                    AuditEvent::PasswordResetCompleted,
                    $user,
                    $user,
                );

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['No se pudo restablecer la contraseña con estos datos.'],
            ]);
        }

        return response()->json([
            'message' => 'La contraseña fue restablecida correctamente.',
        ]);
    }
}
