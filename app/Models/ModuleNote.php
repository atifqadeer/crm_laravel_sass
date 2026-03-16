<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ModuleNote extends Model
{
    use Searchable;

    protected $table = 'module_notes';
    protected $fillable = [
        // 'id',
        'module_note_uid',
        'user_id',
        'module_noteable_id',
        'module_noteable_type',
        'details',
        'status',
        'created_at',
        'updated_at'
    ];
    public function module_noteable()
    {
        return $this->morphTo();
    }
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => $this->user_id,
            'module_noteable_id' => $this->module_noteable_id,
            'module_noteable_type' => $this->module_noteable_type,
            'details' => $this->details,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($note) {
    //         // Generate MD5 hash of the newly assigned ID
    //         $note->module_note_uid = md5($note->id);
    //         $note->save(); // Save after assigning UID
    //     });
    // }

}
