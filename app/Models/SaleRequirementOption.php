<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

/**
 * A selectable chip on the sale form. For options in a single (lead) group,
 * "text" is the sentence wording and connector/suffix join it to the other selections.
 */
class SaleRequirementOption extends Model
{
    protected $table = 'sale_requirement_options';
    protected $fillable = [
        'group_id',
        'label',
        'text',
        'connector',
        'suffix',
        'is_active',
        'sort_order',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(SaleRequirementGroup::class, 'group_id');
    }
}
