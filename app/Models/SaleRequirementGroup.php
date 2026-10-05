<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

/**
 * A titled set of options inside a sale requirement field.
 * A "single" group is the pick-one lead placed first in the composed sentence.
 * Other groups may lead their chips with a prefix and an optional number, e.g. "3 double shift 10 am to 11:30 pm".
 */
class SaleRequirementGroup extends Model
{
    protected $table = 'sale_requirement_groups';
    protected $fillable = [
        'field_id',
        'title',
        'is_single',
        'prefix',
        'has_quantity',
        'sort_order',
    ];
    protected $casts = [
        'is_single' => 'boolean',
        'has_quantity' => 'boolean',
    ];

    public function field()
    {
        return $this->belongsTo(SaleRequirementField::class, 'field_id');
    }

    public function options()
    {
        return $this->hasMany(SaleRequirementOption::class, 'group_id')->orderBy('sort_order')->orderBy('id');
    }
}
