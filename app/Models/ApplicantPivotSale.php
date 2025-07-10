<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class ApplicantPivotSale extends Model
{
    protected $table = 'applicants_pivot_sales';
    protected $fillable = [
        'pivot_uid',
        'applicant_id',
        'sale_id',
        'is_interested'
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
