<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class CrmNote extends Model
{
    use HasFactory, Searchable;

    protected $table = 'crm_notes';
    protected $fillable = [
        //'id',
        'crm_notes_uid',
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

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, h:i A') : '-';
    }
    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d M Y, h:i A') : '-';
    }
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
