<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpAddress extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'ip_addresses';
    protected $fillable = [
        'user_id', 
        'ip_address', 
        'mac_address', 
        'device_type',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
}
