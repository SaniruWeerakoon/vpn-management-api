<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_name',
        'status',
        'notes',
        'config',
        'last_error',
        'revoked_at',
        'provisioned_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
