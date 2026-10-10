<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Requests\Audit\ListAuditLogsRequest;
use App\Http\Resources\AuditLogCollection;
use App\Services\AuditLogQueryService;

class AuditLogController
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogQueryService,
    ) {}

    public function index(ListAuditLogsRequest $request): AuditLogCollection
    {
        $filters = $request->validated();

        if (isset($filters['event'])) {
            $filters['event'] = AuditEvent::from($filters['event']);
        }

        if (isset($filters['actor_id'])) {
            $filters['actor_id'] = (int) $filters['actor_id'];
        }

        return AuditLogCollection::make($this->auditLogQueryService->list($filters));
    }
}
