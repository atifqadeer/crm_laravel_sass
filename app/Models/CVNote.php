<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class CVNote extends Model
{
    use HasFactory, Searchable;
    protected $table = 'cv_notes';
    protected $fillable = [
        // 'id',
        'cv_uid',
        'user_id',
        'sale_id', 
        'applicant_id',
        'details',
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
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

}
