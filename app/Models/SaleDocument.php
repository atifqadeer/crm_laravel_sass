<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class SaleDocument extends Model
{
    protected $table = 'sale_documents';
    protected $fillable = [
        'sale_id',
        'document_name',
        'document_path',
        'document_size',
        'document_extension'
    ];

    /**
     * Get the sale that owns the document.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
