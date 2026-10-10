<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditService
{
    public function record(
        AuditEvent $event,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?string $requestId = null,
    ): AuditLog {
        $this->assertAllowedMetadata($event, $metadata);
        $requestId ??= $this->currentRequestId();

        $auditLog = new AuditLog;
        $auditLog->forceFill([
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'request_id' => $requestId,
            'created_at' => now(),
        ]);
        $auditLog->save();

        return $auditLog;
    }

    private function currentRequestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $requestId = request()->attributes->get('request_id');

        return is_string($requestId) ? $requestId : null;
    }

    private function assertAllowedMetadata(AuditEvent $event, array $metadata): void
    {
        $allowedKeys = match ($event) {
            AuditEvent::UserUpdated => ['fields'],
            AuditEvent::UserRoleChanged => ['old_role', 'new_role'],
            AuditEvent::AuthorizationDenied => ['action'],
            default => [],
        };

        $unexpectedKeys = array_diff(array_keys($metadata), $allowedKeys);

        if ($unexpectedKeys !== []) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected audit metadata for %s: %s',
                $event->value,
                implode(', ', $unexpectedKeys),
            ));
        }
    }
}
