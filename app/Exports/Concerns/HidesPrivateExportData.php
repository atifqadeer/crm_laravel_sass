<?php

namespace App\Exports\Concerns;

use App\Traits\HidesPrivateContacts;
use Horsefly\Sale;

/**
 * Private-data rules for exports (users without show-private-data). On top of the
 * notes rule used by the pages, exports also leave out anything with a hidden job
 * source (hide_private_data): offices, units, sales and applicants.
 */
trait HidesPrivateExportData
{
    use HidesPrivateContacts;

    /** Leave out rows whose job source column is a hidden job source; rows without one are kept. */
    protected function exportExcludeSourceColumn($query, string $sourceColumn)
    {
        $sourceIds = $this->hiddenPrivateSourceIds();

        if ($sourceIds === []) {
            return $query;
        }

        return $query->where(function ($q) use ($sourceColumn, $sourceIds) {
            $q->whereNull($sourceColumn)->orWhereNotIn($sourceColumn, $sourceIds);
        });
    }

    /** Private offices: notes contain a private word, or the office has a hidden job source. */
    protected function exportExcludeOffices($query, string $table = 'offices')
    {
        $this->excludePrivateNotes($query, "{$table}.office_notes");

        return $this->exportExcludeSourceColumn($query, "{$table}.job_source_id");
    }

    /** Private units: notes contain a private word, or the unit has a hidden job source. */
    protected function exportExcludeUnits($query, string $table = 'units')
    {
        $this->excludePrivateNotes($query, "{$table}.unit_notes");

        return $this->exportExcludeSourceColumn($query, "{$table}.job_source_id");
    }

    /**
     * Apply exportExcludeOffices/Units to whichever of `offices` / `units` the query joins.
     * With a left join, rows without an office/unit are kept.
     */
    protected function exportExcludeOfficesAndUnits($query)
    {
        if ($this->privateDataContext() === null) {
            return $query;
        }

        $base = method_exists($query, 'getQuery') ? $query->getQuery() : $query;
        $joinedTables = collect($base->joins ?? [])->pluck('table');

        if ($joinedTables->contains('offices')) {
            $this->exportExcludeOffices($query);
        }
        if ($joinedTables->contains('units')) {
            $this->exportExcludeUnits($query);
        }

        return $query;
    }

    /**
     * Leave out rows that belong to a private sale: the sale, its office or its unit has a
     * hidden job source, or the office/unit notes contain a private word. $saleIdColumn holds
     * the sale id (e.g. 'sales.id', 'crm_notes.sale_id'). Rows without a sale id are kept.
     */
    protected function exportExcludeSales($query, string $saleIdColumn)
    {
        $patterns = $this->privateNoteLikePatterns();
        $sourceIds = $this->hiddenPrivateSourceIds();

        if ($patterns === [] && $sourceIds === []) {
            return $query;
        }

        return $query->where(function ($outer) use ($saleIdColumn, $patterns, $sourceIds) {
            $outer->whereNull($saleIdColumn)
                ->orWhereNotIn($saleIdColumn, function ($sub) use ($patterns, $sourceIds) {
                    $sub->select('private_sales.id')
                        ->from('sales as private_sales')
                        ->leftJoin('offices as private_offices', 'private_offices.id', '=', 'private_sales.office_id')
                        ->leftJoin('units as private_units', 'private_units.id', '=', 'private_sales.unit_id')
                        ->where(function ($match) use ($patterns, $sourceIds) {
                            foreach ($patterns as $like) {
                                $match->orWhere('private_offices.office_notes', 'LIKE', $like)
                                    ->orWhere('private_units.unit_notes', 'LIKE', $like);
                            }

                            if ($sourceIds !== []) {
                                $match->orWhereIn('private_sales.job_source_id', $sourceIds)
                                    ->orWhereIn('private_offices.job_source_id', $sourceIds)
                                    ->orWhereIn('private_units.job_source_id', $sourceIds);
                            }
                        });
                });
        });
    }

    /** True when this sale exists and exportExcludeSales() would leave it out. */
    protected function exportIsPrivateSale($saleId): bool
    {
        if (empty($saleId) || $this->privateDataContext() === null) {
            return false;
        }

        $sale = Sale::query()->whereKey($saleId);

        return (clone $sale)->exists()
            && !$this->exportExcludeSales($sale, 'sales.id')->exists();
    }
}
