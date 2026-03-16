<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class SaleNote extends Model
{
    use HasFactory;
    protected $table = 'sale_notes';
    protected $fillable = [
        // 'id',
        'sales_notes_uid',
        'sale_id',
        'user_id',
        'sale_note',
        'status',
        'created_at',
        'updated_at'
    ];
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => $this->user_id,
            'sale_id' => $this->sale_id,
            'sale_note' => $this->sale_note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
