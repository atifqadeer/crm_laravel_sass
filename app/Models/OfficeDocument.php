<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficeDocument extends Model
{
    use SoftDeletes;

    protected $table = 'offices_documents';

    protected $fillable = [
        'office_id',
        'user_id',
        'document_name',
        'document_path',
        'document_size',
        'document_extension',
        'delete_reason',
        'deleted_by',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getUrlAttribute(): string
    {
        // File names keep their original spelling (spaces, #, % ...), so encode them for the link.
        return asset(dirname($this->document_path) . '/' . rawurlencode(basename($this->document_path)));
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->document_size;

        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
    }
}
