<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A staff member; staff have exactly one role.
 *
 * @mixin User
 */
class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'role' => $this->roles->first()?->name,
            'status' => $this->status,
            'blocked_reason' => $this->blocked_reason,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
