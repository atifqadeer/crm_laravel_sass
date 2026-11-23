<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

/**
 * A call the AI agent made to an applicant about a sale (job).
 * See App\Support\AiCallStatus for the statuses and how the CRM shows them.
 */
class AiAgentCall extends Model
{
    protected $table = 'ai_agent_calls';

    protected $fillable = [
        'call_id',
        'applicant_id',
        'sale_id',
        'call_status',
        'outcome',
        'status',
        'callback_at',
        'called_at',
        'duration_seconds',
        'summary',
        'transcript',
        'recording_url',
        'payload',
    ];

    protected $casts = [
        'callback_at' => 'datetime',
        'called_at' => 'datetime',
        'duration_seconds' => 'integer',
        'payload' => 'array',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
