<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    protected $fillable = [
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_landline',
        'contact_note',
        'job_source_id',
        'contactable_id',
        'contactable_type'
    ];

    public function contactable()
    {
        return $this->morphTo();
    }

    public function jobSource()
    {
        return $this->belongsTo(JobSource::class, 'job_source_id');
    }
}
