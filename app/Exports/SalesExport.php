<?php

namespace App\Exports;

use Horsefly\Sale;
use Horsefly\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesExport implements FromCollection, WithHeadings
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
        switch ($this->type) {
            case 'emails':
                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    )
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'declined':
                return Applicant::query()
                    ->with([
                        'jobTitle',
                        'jobCategory',
                        'jobSource'
                    ])
                    ->select([
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    ])
                    ->where('applicants.status', 1)
                    ->distinct('applicants.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["declined"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_declined"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => ucwords($item->job_category),
                            'job_type' => ucwords(str_replace('-',' ',$item->job_type)),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'not_attended':
                return Applicant::query()
                    ->with([
                        'jobTitle',
                        'jobCategory',
                        'jobSource'
                    ])
                    ->select([
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    ])
                    ->where('applicants.status', 1)
                    ->distinct('applicants.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["interview_not_attended"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_interview_not_attended"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => ucwords($item->job_category),
                            'job_type' => ucwords(str_replace('-',' ',$item->job_type)),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
            
            case 'start_date_hold':
                return Applicant::query()
                    ->with([
                        'jobTitle',
                        'jobCategory',
                        'jobSource'
                    ])
                    ->select([
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    ])
                    ->where('applicants.status', 1)
                    ->distinct('applicants.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["start_date_hold", "start_date_hold_save"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_start_date_hold", "crm_start_date_hold_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => ucwords($item->job_category),
                            'job_type' => ucwords(str_replace('-',' ',$item->job_type)),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
            case 'dispute':
                return Applicant::query()
                    ->with([
                        'jobTitle',
                        'jobCategory',
                        'jobSource'
                    ])
                    ->select([
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    ])
                    ->where('applicants.status', 1)
                    ->distinct('applicants.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["dispute"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_dispute"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => ucwords($item->job_category),
                            'job_type' => ucwords(str_replace('-',' ',$item->job_type)),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
            
            case 'paid':
                return Applicant::query()
                    ->with([
                        'jobTitle',
                        'jobCategory',
                        'jobSource'
                    ])
                    ->select([
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    ])
                    ->where('applicants.status', 1)
                    ->distinct('applicants.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["paid"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_paid"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => ucwords($item->job_category),
                            'job_type' => ucwords(str_replace('-',' ',$item->job_type)),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'emailsOpen':
                $latestAuditSub = DB::table('audits')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('auditable_type', 'Horsefly\Sale')
                    ->where('message', 'like', '%sale-opened%')
                    ->whereIn('auditable_id', function($query) {
                        $query->select('id')
                            ->from('sales')
                            ->where('status', 1); // Ensure we only consider closed sales
                    })
                    ->groupBy('auditable_id');

                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at',
                        'audits.created_at as open_date'
                    )
                    ->where('sales.status', 1)
                    ->where('sales.is_on_hold', 0)
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->leftJoin('audits', function ($join) use ($latestAuditSub) {
                        $join->on('audits.auditable_id', '=', 'sales.id')
                            ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                            ->where('audits.message', 'like', '%sale-opened%')
                            ->whereIn('audits.id', $latestAuditSub);
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                            'open_date' => $item->open_date ? Carbon::parse($item->open_date)->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'emailsClose':
                $latestAuditSub = DB::table('audits')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('auditable_type', 'Horsefly\Sale')
                    ->where('message', 'like', '%sale-closed%')
                    ->whereIn('auditable_id', function($query) {
                        $query->select('id')
                            ->from('sales')
                            ->where('status', 0); // Ensure we only consider closed sales
                    })
                    ->groupBy('auditable_id');

                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_email',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at',
                        'audits.created_at as closed_date'
                    )
                    ->where('sales.status', 0)
                    ->where('sales.is_on_hold', 0)
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->leftJoin('audits', function ($join) use ($latestAuditSub) {
                        $join->on('audits.auditable_id', '=', 'sales.id')
                            ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                            ->where('audits.message', 'like', '%sale-closed%')
                            ->whereIn('audits.id', $latestAuditSub);
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_email' => $item->contact_email,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                            'closed_date' => $item->closed_date ? Carbon::parse($item->closed_date)->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            case 'noLatLong':
                return Sale::select(
                        'sales.id', 
                        'offices.office_name',
                        'units.unit_name',
                        'sales.sale_postcode',
                        'sales.lat as sale_lat',
                        'sales.lng as sale_lng',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    )
                    ->whereNull('sales.lat')
                    ->whereNull('sales.lng')
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'sale_lat' => $item->sale_lat,
                            'sale_lng' => $item->sale_lng,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            case 'all':
                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_name',
                        'contacts.contact_email',
                        'contacts.contact_phone',
                        'contacts.contact_landline',
                        'contacts.contact_note',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at'
                    )
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_name' => ucwords(strtolower($item->contact_name)),
                            'contact_email' => $item->contact_email,
                            'contact_phone' => $item->contact_phone,
                            'contact_landline' => $item->contact_landline,
                            'contact_note' => $item->contact_note,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'allOpen':
                $latestAuditSub = DB::table('audits')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('auditable_type', 'Horsefly\Sale')
                    ->where('message', 'like', '%sale-opened%')
                    ->whereIn('auditable_id', function($query) {
                        $query->select('id')
                            ->from('sales')
                            ->where('status', 1); // Ensure we only consider closed sales
                    })
                    ->groupBy('auditable_id');

                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_name',
                        'contacts.contact_email',
                        'contacts.contact_phone',
                        'contacts.contact_landline',
                        'contacts.contact_note',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at',
                        'audits.created_at as open_date'
                    )
                    ->where('sales.status', 1)
                    ->where('sales.is_on_hold', 0)
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                    ->leftJoin('audits', function ($join) use ($latestAuditSub) {
                        $join->on('audits.auditable_id', '=', 'sales.id')
                            ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                            ->where('audits.message', 'like', '%sale-opened%')
                            ->whereIn('audits.id', $latestAuditSub);
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_name' => ucwords(strtolower($item->contact_name)),
                            'contact_email' => $item->contact_email,
                            'contact_phone' => $item->contact_phone,
                            'contact_landline' => $item->contact_landline,
                            'contact_note' => $item->contact_note,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                            'open_date' => $item->open_date ? Carbon::parse($item->open_date)->format('d M Y, h:i A') : 'N/A',
                        ];
                    });

            case 'allClose':
                $latestAuditSub = DB::table('audits')
                    ->select(DB::raw('MAX(id) as id'))
                    ->where('auditable_type', 'Horsefly\Sale')
                    ->where('message', 'like', '%sale-closed%')
                    ->whereIn('auditable_id', function($query) {
                        $query->select('id')
                            ->from('sales')
                            ->where('status', 0); // Ensure we only consider closed sales
                    })
                    ->groupBy('auditable_id');

                return Sale::select(
                        'sales.id', 
                        'offices.office_name', 
                        'units.unit_name',
                        'sales.sale_postcode',
                        'contacts.contact_name',
                        'contacts.contact_email',
                        'contacts.contact_phone',
                        'contacts.contact_landline',
                        'contacts.contact_note',
                        'job_categories.name as job_category',
                        'sales.job_type',
                        'job_titles.name as job_title',
                        'sales.created_at',
                        'audits.created_at as closed_date'
                    )
                    ->where('sales.status', 0)
                    ->where('sales.is_on_hold', 0)
                    ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
                    ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
                    ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
                    ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
                    ->leftJoin('contacts', 'units.id', '=', 'contacts.contactable_id')
                    ->where('contacts.contactable_type', 'Horsefly\\Unit')
                     ->leftJoin('audits', function ($join) use ($latestAuditSub) {
                        $join->on('audits.auditable_id', '=', 'sales.id')
                            ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                            ->where('audits.message', 'like', '%sale-closed%')
                            ->whereIn('audits.id', $latestAuditSub);
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'office_name' => ucwords(strtolower($item->office_name)),
                            'unit_name' => ucwords(strtolower($item->unit_name)),
                            'sale_postcode' => strtoupper($item->sale_postcode),
                            'contact_name' => ucwords(strtolower($item->contact_name)),
                            'contact_email' => $item->contact_email,
                            'contact_phone' => $item->contact_phone,
                            'contact_landline' => $item->contact_landline,
                            'contact_note' => $item->contact_note,
                            'job_category' => strtoupper($item->job_category),
                            'job_type' => strtoupper($item->job_type),
                            'job_title' => strtoupper($item->job_title),
                            'created_at' => $item->created_at ? $item->created_at->format('d M Y, h:i A') : 'N/A',
                            'closed_date' => $item->closed_date ? Carbon::parse($item->closed_date)->format('d M Y, h:i A') : 'N/A',
                        ];
                    });
                
            default:
            return collect(); // Return empty collection instead of null
        }
    }

    public function headings(): array
    {
        switch ($this->type) {
            case 'emails':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'declined':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'not_attended':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'start_date_hold':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'dispute':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'paid':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'emailsOpen':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At', 'Open Date'];
            case 'emailsClose':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Email', 'Job Category', 'Job Type', 'Job Title', 'Created At', 'Close Date'];
            case 'noLatLong':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Latitude', 'Longitude', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'all':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Name', 'Contact Email', 'Contact Phone', 'Contact Landline', 'Contact Note', 'Job Category', 'Job Type', 'Job Title', 'Created At'];
            case 'allOpen':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Name', 'Contact Email', 'Contact Phone', 'Contact Landline', 'Contact Note', 'Job Category', 'Job Type', 'Job Title', 'Created At', 'Open Date'];
            case 'allClose':
                return ['ID', 'Head Office Name', 'Unit Name', 'Sale Postcode', 'Contact Name', 'Contact Email', 'Contact Phone', 'Contact Landline', 'Contact Note', 'Job Category', 'Job Type', 'Job Title', 'Created At', 'Close Date'];
            default:
                return [];
        }
    }
}
