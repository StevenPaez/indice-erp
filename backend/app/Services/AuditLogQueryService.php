<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogQueryService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('actor:id,name,email')
            ->when(
                $filters['event'] ?? null,
                fn ($query, AuditEvent $event) => $query->where('event', $event),
            )
            ->when(
                $filters['actor_id'] ?? null,
                fn ($query, int $actorId) => $query->where('actor_id', $actorId),
            )
            ->when(
                $filters['request_id'] ?? null,
                fn ($query, string $requestId) => $query->where('request_id', $requestId),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, string $date) => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, string $date) => $query->whereDate('created_at', '<=', $date),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);
    }
}
