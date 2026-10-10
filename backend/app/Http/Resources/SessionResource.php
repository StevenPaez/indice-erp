<?php

namespace App\Http\Resources;

use App\Enums\Capability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'capabilities' => Capability::valuesFor($this->role),
        ];
    }
}
