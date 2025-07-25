<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Message extends Model
{
    protected $table = 'messages';
    protected $fillable=[
      'time',
      'date',
      'is_read',
      'is_sent',
      'message',
      'status',
      'phone_number',
      'msg_id',
      'user_id',
      'module_id',
      'module_type'
    ];
    protected $appends = ['FormattedTime'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'module_id')
            ->where('module_type', 'Horsefly\\Applicant');
    }

    public function getFormattedTimeAttribute()
    {
       return Carbon::parse($this->time)->format('h:i A');// Format the time as "11:10 AM"
    }
}
