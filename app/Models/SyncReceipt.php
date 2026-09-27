<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Marks an offline item as applied. The unique key makes syncing the same item twice a no-op.
 */
#[Fillable(['key', 'type', 'user_id'])]
class SyncReceipt extends Model
{
    /**
     * Dedupe key for a queued item: its client UUID, or (for items queued by older clients
     * without one) a hash of its type and data, which is identical on every retry.
     *
     * @param array{uuid?: mixed, type?: mixed, data?: mixed} $item
     */
    public static function keyFor(array $item): string
    {
        $uuid = $item['uuid'] ?? null;
        if (is_string($uuid) && Str::isUuid($uuid)) {
            return strtolower($uuid);
        }

        return hash('sha256', json_encode([$item['type'] ?? null, $item['data'] ?? null]));
    }
}
