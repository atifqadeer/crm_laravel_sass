<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ApplicantNote extends Model
{
    protected $table = 'applicant_notes';
    protected $fillable = [
        'applicant_id',
        'note_uid',
        'user_id',
        'details',
        'moved_tab_to',
        'status'
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
}
