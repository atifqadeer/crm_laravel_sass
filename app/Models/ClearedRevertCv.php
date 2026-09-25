<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ClearedRevertCv extends Model
{
    protected $table = 'cleared_revert_cvs';
    protected $fillable = [
        'cv_note_id',
        'applicant_id',
        'sale_id',
        'user_id',
        'reverted_by',
        'details',
        'created_at',
        'updated_at'
    ];
}
