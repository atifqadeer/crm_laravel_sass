<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class History extends Model
{
    use HasFactory, Searchable;
    protected $table = 'history';
    protected $fillable = [
        // 'id',
        'history_uid',
        'user_id',
        'applicant_id',
        'sale_id',
        'stage',
        'sub_stage',
        'status',
        'created_at',
        'updated_at'
    ];
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => $this->user_id,
            'applicant_id' => $this->applicant_id,
            'sale_id' => $this->sale_id,
            'stage' => $this->stage,
            'sub_stage' => $this->sub_stage,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
}
