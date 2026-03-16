<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class CrmRejectedCv extends Model
{
    use Searchable;

    protected $table = 'crm_rejected_cv';
    protected $fillable = [
        //'id',
        'crm_rejected_cv_uid',
        'applicant_id',
        'user_id',
        'crm_note_id',
        'sale_id',
        'reason',
        'crm_rejected_cv_note',
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
            'reason' => $this->reason,
            'crm_rejected_cv_note' => $this->crm_rejected_cv_note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
