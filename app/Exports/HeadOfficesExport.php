<?php

namespace App\Exports;

use Horsefly\Office;
use Horsefly\Contact;
use App\Traits\HidesPrivateContacts;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HeadOfficesExport implements FromCollection, WithHeadings
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
                $query = Office::select(
                        'offices.id', 
                        'offices.office_name', 
                        'offices.office_postcode',
                        'contacts.contact_email',
                        'offices.created_at'
                    )
                    ->leftJoin('contacts', 'offices.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Office');

                $this->excludePrivateContacts($query);
                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'office_postcode' => strtoupper($item->office_postcode),
                            'contact_email' => $item->contact_email,
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            case 'noLatLong':
                $query = Office::select(
                        'id',
                        'office_name',
                        'office_postcode',
                        'office_lat',
                        'office_lng',
                        'created_at'
                    )
                    ->whereIn('office_lng', ['0', '', null])
                    ->whereIn('office_lat', ['0', '', null]);

                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id'              => $item->id,
                            'office_name'     => ucwords(strtolower($item->office_name)),
                            'office_postcode' => strtoupper($item->office_postcode),
                            'office_lat'      => $item->office_lat,
                            'office_lng'      => $item->office_lng,
                            'created_at'      => $item->created_at
                                                    ? $item->created_at->format('d M Y, h:i A')
                                                    : 'N/A',
                        ];
                    });
                
            case 'all':
                $query = Office::select(
                        'offices.id', 
                        'offices.office_name', 
                        'contacts.contact_name', 
                        'contacts.contact_email',
                        'contacts.contact_phone',
                        'contacts.contact_landline',
                        'offices.created_at'
                    )
                    ->leftJoin('contacts', 'offices.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Office');

                $this->excludePrivateContacts($query);
                $this->applyListFilters($query);

                return $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'office_postcode' => strtoupper($item->office_postcode),
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
     * Match the head office list filters (status, search).
     */
    protected function applyListFilters($query)
    {
        $query->whereNull('offices.deleted_at')
            ->whereNotIn('offices.status', [4, 5]);

        $status = strtolower(trim((string) ($this->filters['status_filter'] ?? '')));
        if ($status === 'active') {
            $query->where('offices.status', 1);
        } elseif ($status === 'inactive') {
            $query->where('offices.status', 0);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if (strlen($search) >= 2) {
            $officeIds = Office::search($search)->keys()->toArray();
            $contactIds = Contact::where('contactable_type', 'Horsefly\\Office')
                ->where(function ($q) use ($search) {
                    $q->where('contact_email', 'LIKE', "%{$search}%")
                        ->orWhere('contact_phone', 'LIKE', "%{$search}%")
                        ->orWhere('contact_landline', 'LIKE', "%{$search}%");
                })
                ->pluck('contactable_id')
                ->toArray();

            $allIds = array_unique(array_merge($officeIds, $contactIds));
            $query->whereIn('offices.id', $allIds !== [] ? $allIds : [0]);
        }

        return $query;
    }

    public function headings(): array
    {
        switch ($this->type) {
            case 'emails':
                return ['ID', 'Head Office Name', 'Postcode', 'Contact Email', 'Created At'];
            case 'noLatLong':
                return ['ID', 'Head Office Name', 'Postcode', 'Latitude', 'Longitude', 'Created At'];
            case 'all':
                return ['ID', 'Head Office Name', 'Postcode', 'Contact Name', 'Contact Email', 'Contact Phone', 'Contact Landline', 'Created At'];
            default:
                return [];
        }
    }
}
