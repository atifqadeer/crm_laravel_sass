<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallLog extends Model
{
    use HasFactory;

    protected $table = 'call_logs';

    protected $fillable = [
        'user_id',
        'caller_number',
        'caller_name',
        'direction',      // 'inbound' | 'outbound' | 'missed'
        'duration_seconds',
        'sip_call_id',
        'applicant_id',
        'sale_id',
        'source',         // 'browser' | 'desktop'
        'called_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'called_at'        => 'datetime',
        'user_id'          => 'integer',
        'applicant_id'     => 'integer',
        'sale_id'          => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        // User lives in the Horsefly namespace (not App\Models) — the previous
        // \App\Models\User reference resolved to a non-existent class.
        return $this->belongsTo(User::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
