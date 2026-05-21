<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'resource', 'ip_address', 'success', 'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'success'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $resource = null, bool $success = true, array $meta = []): void
    {
        $user = Auth::user();
        static::create([
            'user_id'    => $user?->id,
            'user_name'  => $user?->name ?? 'System',
            'action'     => $action,
            'resource'   => $resource,
            'ip_address' => Request::ip(),
            'success'    => $success,
            'meta'       => $meta ?: null,
        ]);
    }
}
