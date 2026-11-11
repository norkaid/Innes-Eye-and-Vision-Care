<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records administrative actions. Only safe metadata is stored – never passwords,
 * tokens or full form contents.
 */
class AuditLogger
{
    private const FORBIDDEN_KEYS = ['password', 'password_confirmation', 'token', 'remember_token', 'staff_note', 'note', 'message', 'body'];

    /** @param array<string,mixed> $metadata */
    public function log(string $action, ?Model $entity = null, array $metadata = []): void
    {
        $metadata = array_diff_key($metadata, array_flip(self::FORBIDDEN_KEYS));

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
