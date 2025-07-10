<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Horsefly\Audit;

class SaleNote extends Model
{
    protected $table = 'sale_notes';
    protected $fillable = [
        'sales_notes_uid',
        'sale_id',
        'user_id',
        'sale_note',
        'status'
    ];
}
