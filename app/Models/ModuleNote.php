<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ModuleNote extends Model
{
    protected $table = 'module_notes';
    protected $fillable = [
        'module_note_uid',
        'user_id',
        'module_noteable_id',
        'module_noteable_type',
        'details',
        'status'
    ];

}
