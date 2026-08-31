<?php

namespace App\Exports;

use Horsefly\Unit;
use Horsefly\Setting;
use Horsefly\JobSource;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Gate;

class UnitsExport implements FromCollection, WithHeadings
{
    protected $type;

    public function __construct(string $type = 'all')
    {
        $this->type = $type;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $sourceIds = $this->hiddenJobSourceIds();

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

                $this->excludePrivateContacts($query, $sourceIds);

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
                return Unit::select(
                    'id',
                    'unit_name',
                    'unit_postcode',
                    'lat',
                    'lng',
                    'created_at'
                )
                    ->where(function ($query) {
                        $query->where('lat', '0')
                            ->orWhereNull('lat')
                            ->orWhere('lat', '');
                    })
                    ->where(function ($query) {
                        $query->where('lng', '0')
                            ->orWhereNull('lng')
                            ->orWhere('lng', '');
                    })
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

                $this->excludePrivateContacts($query, $sourceIds);

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
     * Job source IDs that must stay hidden for users without show-private-data.
     */
    private function hiddenJobSourceIds(): array
    {
        $hidePrivateDataSetting = Setting::where('key', 'hide_private_data')->value('value');
        $hidePrivateData = array_filter(
            array_map('trim', explode(',', $hidePrivateDataSetting ?? ''))
        );

        if (Gate::allows('show-private-data') || count($hidePrivateData) === 0) {
            return [];
        }

        return JobSource::where('is_active', 1)
            ->where(function ($q) use ($hidePrivateData) {
                foreach ($hidePrivateData as $hideName) {
                    $q->orWhere('name', 'LIKE', '%' . $hideName . '%');
                }
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Drop contact rows whose job_source_id matches hide_private_data sources.
     */
    private function excludePrivateContacts($query, array $sourceIds)
    {
        if (count($sourceIds) === 0) {
            return $query;
        }

        return $query->where(function ($q) use ($sourceIds) {
            $q->whereNotIn('contacts.job_source_id', $sourceIds)
                ->orWhereNull('contacts.job_source_id');
        });
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
