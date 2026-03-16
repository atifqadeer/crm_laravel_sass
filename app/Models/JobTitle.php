<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Laravel\Scout\Searchable;

class JobTitle extends Model
{
    use HasFactory, Searchable;

    protected $table = 'job_titles';
    protected $fillable = [
        'name',
        'type',
        'job_category_id',
        'description',
        'is_active',
        'related_titles'
    ];
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'job_category_id' => $this->job_category_id,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'related_titles' => $this->related_titles,
        ];
    }
    protected $casts = [
        'related_titles' => 'array',
        'is_active' => 'boolean',
    ];

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'job_title_id');
    }
    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }
}
