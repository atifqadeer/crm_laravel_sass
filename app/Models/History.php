<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'history';
    protected $fillable = [
        'history_uid',
        'user_id',
        'applicant_id',
        'sale_id',
        'stage',
        'sub_stage',
        'status',
    ];
}
