<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class RevertStage extends Model
{
    protected $table = 'revert_stages';

    protected $fillable = [
        'applicant_id',
        'sale_id',
        'user_id',
        'notes',
        'stage',
    ];
}
