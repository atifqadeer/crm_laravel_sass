<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'contacts';
    protected $fillable = [
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_landline',
        'contact_note',
        'contactable_id',
        'contactable_type'
    ];
    // public function office()
    // {
    //     return $this->belongsTo(Office::class);
    // }
    public function contactable()
    {
        return $this->morphTo();
    }
}
