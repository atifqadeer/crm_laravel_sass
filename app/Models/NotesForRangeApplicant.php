<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class NotesForRangeApplicant extends Model
{
    protected $table = 'notes_for_range_applicants';

    protected $fillable = [
        'range_uid',
        'applicants_pivot_sales_id',
        'reason',
        'status',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
}
