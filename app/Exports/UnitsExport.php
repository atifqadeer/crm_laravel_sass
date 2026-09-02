<?php

namespace App\Exports;

use Horsefly\Unit;
use Horsefly\Office;
use Horsefly\Contact;
use App\Traits\HidesPrivateContacts;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnitsExport implements FromCollection, WithHeadings
{
    use HidesPrivateContacts;

    protected $type;
    protected $filters;

    public function __construct(string $type = 'all', array $filters = [])
    {
        $this->type = $type;
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        switch ($this->type) {
            case 'emails':
                $query = Unit::select(
                        'units.id', 
                        'units.unit_name', 
                        'units.unit_postcode',
                        'contacts.contact_email',
                        'units.created_at'
                    )
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit');

                $this->excludePrivateContacts($query);
                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'unit_postcode' => strtoupper($item->unit_postcode),
                            'contact_email' => $item->contact_email,
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            case 'noLatLong':
                $query = Unit::select(
                        'id', 
                        'unit_name',
                        'unit_postcode',
                        'lat',
                        'lng',
                        'created_at'
                    )
                    ->where(function($query) {
                        $query->where('lat', '0')
                            ->orWhereNull('lat')
                            ->orWhere('lat', '');
                    })
                    ->where(function($query) {
                        $query->where('lng', '0')
                            ->orWhereNull('lng')
                            ->orWhere('lng', '');
                    });

                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'unit_postcode' => strtoupper($item->unit_postcode),
                            'lat' => $item->lat,
                            'lng' => $item->lng,
                            'created_at' => $item->created_at 
                                ? $item->created_at->format('d M Y, h:i A') 
                                : 'N/A',
                        ];
                    });
                
            case 'all':
                $query = Unit::select(
                        'units.id', 
                        'offices.office_name', 
                        'units.unit_name', 
                        'units.unit_postcode', 
                        'contacts.contact_name', 
                        'contacts.contact_email',
                        'contacts.contact_phone',
                        'contacts.contact_landline',
                        'units.created_at'
                    )
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->leftJoin('offices', 'units.office_id', '=', 'offices.id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit');

                $this->excludePrivateContacts($query);
                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'unit_postcode' => strtoupper($item->unit_postcode),
                            'contact_name' => ucwords(strtolower($item->contact_name)),
                            'contact_email' => $item->contact_email,
                            'contact_phone' => $item->contact_phone,
                            'contact_landline' => $item->contact_landline,
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            default:
                return collect(); // Return empty collection instead of null
        }
    }

    /**
     * Match the units list filters (status, head office, search).
     */
    protected function applyListFilters($query)
    {
        $query->whereNull('units.deleted_at')
            ->whereNotIn('units.status', [4, 5]);

        $status = strtolower(trim((string) ($this->filters['status_filter'] ?? '')));
        if ($status === 'active') {
            $query->where('units.status', 1);
        } elseif ($status === 'inactive') {
            $query->where('units.status', 0);
        }

        $officeIds = $this->arrayFilter($this->filters['office_filter'] ?? null);
        if ($officeIds !== []) {
            $query->whereIn('units.office_id', $officeIds);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if (strlen($search) >= 2) {
            $unitIdsFromSearch = Unit::search($search)->keys()->toArray();
            $officeIdsFromSearch = Office::search($search)->keys()->toArray();
            $unitIdsByOffice = $officeIdsFromSearch !== []
                ? Unit::whereIn('office_id', $officeIdsFromSearch)->pluck('id')->toArray()
                : [];
            $unitIdsFromContacts = Contact::where('contactable_type', 'Horsefly\\Unit')
                ->where(function ($q) use ($search) {
                    $q->where('contact_email', 'LIKE', "%{$search}%")
                        ->orWhere('contact_phone', 'LIKE', "%{$search}%");
                })
                ->pluck('contactable_id')
                ->toArray();

            $allIds = array_unique(array_merge($unitIdsFromSearch, $unitIdsByOffice, $unitIdsFromContacts));
            $query->whereIn('units.id', $allIds !== [] ? $allIds : [0]);
        }

        return $query;
    }

    protected function arrayFilter($value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        return array_values(array_filter((array) $value, fn ($item) => $item !== '' && $item !== null));
    }

    public function headings(): array
    {
        switch ($this->type) {
            case 'emails':
                return ['Unit Name', 'Postcode', 'Contact Email', 'Created At'];
            case 'noLatLong':
                return ['Unit Name', 'Postcode', 'Latitude', 'Longitude', 'Created At'];
            case 'all':
                return ['Head Office Name', 'Unit Name', 'Unit Postcode', 'Contact Name', 'Contact Email', 'Contact Phone', 'Contact Landline', 'Created At'];
            default:
                return [];
        }
    }
}
