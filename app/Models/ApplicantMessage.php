<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ApplicantMessage extends Model
{
    protected $table = 'applicant_messages';
    protected $fillable=[
      'time',
      'date',
      'is_read',
      'message',
      'status',
      'phone_number',
      'msg_id',
      'user_id',
      'applicant_id'
    ];
    protected $appends = ['FormattedTime'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function applicants(){
        return $this->belongsTo(Applicant::class,'applicant_id');
    }

    public function getFormattedTimeAttribute()
    {
       return Carbon::parse($this->time)->format('h:i A');// Format the time as "11:10 AM"
    }
}
