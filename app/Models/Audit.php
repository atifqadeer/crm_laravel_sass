<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';
    protected $fillable = [
        'user_id',
        'data',
        'message',
        'auditable_id',
        'auditable_type'
    ];
    protected $casts = [
        'data' => 'array',
    ];

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
