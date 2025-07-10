<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class CVNote extends Model
{
    protected $table = 'cv_notes';
    protected $fillable = [
        'cv_uid',
        'user_id',
        'sale_id', 
        'applicant_id',
        'details',
        'status'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
