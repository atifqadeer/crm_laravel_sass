<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class QualityNotes extends Model
{
    use HasFactory, Searchable;
    protected $table = 'quality_notes';
    protected $fillable = [
        // 'id',
        'quality_notes_uid',
        'user_id',
        'applicant_id',
        'sale_id',
        'details',
        'moved_tab_to',
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
            'details' => $this->details,
            'moved_tab_to' => $this->moved_tab_to,
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
