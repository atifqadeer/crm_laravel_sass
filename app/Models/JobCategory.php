<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class JobCategory extends Model
{
    use HasFactory, Searchable;

    protected $table = 'job_categories';
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active
        ];
    }
    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'job_category_id');
    }
    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class, 'job_category_id');
    }
}
