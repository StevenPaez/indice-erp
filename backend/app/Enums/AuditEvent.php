<?php

namespace App\Enums;

enum AuditEvent: string
{
    case AdminBootstrapped = 'system.admin_bootstrapped';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserRoleChanged = 'user.role_changed';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';
    case UserPasswordChanged = 'user.password_changed';
    case LoginSucceeded = 'auth.login_succeeded';
    case LoginFailed = 'auth.login_failed';
    case Logout = 'auth.logout';
    case PasswordResetRequested = 'auth.password_reset_requested';
    case PasswordResetCompleted = 'auth.password_reset_completed';
    case AuthorizationDenied = 'authorization.denied';
}
