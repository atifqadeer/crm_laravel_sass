<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\SaleNote;
use Horsefly\CrmRejectedCv;
use Horsefly\CvNote;
use Horsefly\CrmNote;
use Horsefly\History;
use Horsefly\QualityNotes;
use Horsefly\Applicant;
use Horsefly\EmailTemplate;
use Horsefly\ApplicantMessage;
use Horsefly\User;
use Horsefly\Interview;
use Horsefly\SentEmail;
use Horsefly\RevertStage;
use Horsefly\ModuleNote;
use App\Observers\ActionObserver;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Exception;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Horsefly\SmsTemplate;
use Illuminate\Support\Str;

class CrmController extends Controller
{
    public function index()
    {
        return view('crm.list');
    }
    public function getCrmApplicantsAjaxRequest(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $searchTerm = $request->input('search', ''); // This will get the search query
        $tabFilter = $request->input('tab_filter', ''); // Default is empty (no filter)

        $model = Applicant::query()
            ->with([
                'jobTitle',
                'jobCategory',
                'jobSource',
                'user'
            ])
            ->select([
                'applicants.id',
                'applicants.applicant_name',
                'applicants.applicant_email',
                'applicants.applicant_email_secondary',
                'applicants.applicant_phone',
                'applicants.applicant_postcode',
                'applicants.updated_at',
                'applicants.applicant_notes',
                'applicants.job_category_id',
                'applicants.job_title_id',
                'applicants.job_source_id',
                'applicants.job_type',
                'applicants.paid_status',
                'applicants.paid_timestamp',
                'applicants.lat',
                'applicants.lng',

                /** get job category, title and source name */
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
            ])
            ->where('applicants.status', 1)
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->distinct('applicants.id');


        // Filter by status if it's not empty
        switch ($tabFilter) {
            case 'sent cvs':
                $model->join('quality_notes', function ($join) {
                    $join->on('applicants.id', '=', 'quality_notes.applicant_id')
                        ->whereIn("quality_notes.moved_tab_to", ["cleared"])
                        ->where("quality_notes.status", 1);
                })
                    ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('history', function ($join) {
                        $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('quality_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->on('cv_notes.sale_id', '=', 'sales.id')
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id') // Make optional
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',

                        /** sale */
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        /** units */
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        /** user */
                        'users.name as user_name'
                    ]);

                break;

            case 'open cvs':
                $model->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->where("cv_notes.status", 1)->latest();
                    })
                    ->join('sales', function ($join) {
                        $join->on('cv_notes.sale_id', '=', 'sales.id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id');
                    })
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('revert_stages', function ($join) {
                        $join->on('applicants.id', '=', 'revert_stages.applicant_id')
                            ->on('sales.id', '=', 'revert_stages.sale_id')
                            ->whereIn('revert_stages.id', function ($query) {
                                $query->select(DB::raw('MAX(id)'))
                                    ->from('revert_stages')
                                    ->whereColumn('applicant_id', 'applicants.id')
                                    ->whereColumn('sale_id', 'sales.id')
                                    ->whereIn('stage', [
                                        'quality_note',
                                        'cv_hold',
                                        'no_job_quality_cvs'
                                    ]);
                            });
                    })
                    ->join('history', function ($join) {
                        $join->on('cv_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('cv_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["quality_cvs_hold"])
                            ->where("history.status", 1);
                    })
                    ->join('users', 'users.id', '=', 'revert_stages.user_id')
                    ->addSelect([
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'revert_stages.user_id as revert_user_id',
                        'revert_stages.notes as notes_detail',
                        'revert_stages.stage as revert_stage',
                        'revert_stages.updated_at as notes_created_at',
                        'users.name as user_name'
                    ]);
                break;

            case 'sent cvs (no job)':
                $model->join('quality_notes', function ($join) {
                        $join->on('applicants.id', '=', 'quality_notes.applicant_id')
                            ->whereIn("quality_notes.moved_tab_to", ["cleared", "cleared_no_job"])
                            ->where("quality_notes.status", 1);
                    })
                    ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('quality_notes.applicant_id', '=', 'history.applicant_id')
                            ->on('quality_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["quality_cleared_no_job"])
                            ->where('history.status', 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id')
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'quality_notes.details as notes_detail',
                        'quality_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',

                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;

            case 'rejected cvs':
                $model->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ['cv_sent_reject', 'cv_sent_reject_no_job'])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
                            ->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ['crm_reject', 'crm_no_job_reject'])
                            ->where('history.status', 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',

                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);
                break;

            case 'request':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ['cv_sent_request', 'request_save'])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
                            ->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ['crm_request', 'crm_request_save'])
                            ->where('history.status', 1);
                    })
                    ->leftJoin('interviews', function ($join) {
                        $join->on('applicants.id', '=', 'interviews.applicant_id');
                        $join->on('sales.id', '=', 'interviews.sale_id');
                        $join->where('interviews.status', 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',

                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'interviews.schedule_time',
                        'interviews.schedule_date',
                        'interviews.status as interview_status',

                        'users.name as user_name'
                    ]);
                break;

            case 'request (no job)':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn('crm_notes.moved_tab_to', ['cv_sent_no_job_request', 'request_no_job_save'])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
                            ->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn('history.sub_stage', ['crm_no_job_request', 'crm_request_no_job_save'])
                            ->where('history.status', 1);
                    })
                    ->leftJoin('interviews', function ($join) {
                        $join->on('applicants.id', '=', 'interviews.applicant_id');
                        $join->on('sales.id', '=', 'interviews.sale_id');
                        $join->where('interviews.status', 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'interviews.schedule_time',
                        'interviews.schedule_date',
                        'interviews.status as interview_status',

                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'rejected by request':
                $model->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["request_reject", "request_no_job_reject"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_request_reject", "crm_request_no_job_reject"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'confirmation':
                $model->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["request_confirm", "interview_save", "request_no_job_confirm"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_request_confirm", "crm_interview_save", "crm_request_no_job_confirm"])
                            ->where("history.status", 1);
                    })
                    ->leftJoin('interviews', function ($join) {
                        $join->on('applicants.id', '=', 'interviews.applicant_id');
                        $join->on('sales.id', '=', 'interviews.sale_id');
                        $join->where('interviews.status', 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'interviews.schedule_time',
                        'interviews.schedule_date',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'rebook':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["rebook", "rebook_save"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_rebook", "crm_rebook_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'attended to pre-start date':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["interview_attended", "prestart_save"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_interview_attended", "crm_prestart_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'declined':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["declined"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
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
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'not attended':
                $model->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["interview_not_attended"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_interview_not_attended"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id')
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'start date':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["start_date", "start_date_save", "start_date_back"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ['crm_start_date', 'crm_start_date_save', 'crm_start_date_back'])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'start date hold':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["start_date_hold", "start_date_hold_save"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
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
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'invoice':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["invoice", "final_save"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_invoice", "crm_final_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'invoice sent':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["invoice_sent", "final_save"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('history', function ($join) {
                        $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('crm_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["crm_invoice_sent", "crm_final_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id') // Fixed: Compare columns, not strings
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'dispute':
                $model->join('crm_notes', function ($join) {
                    $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                        ->whereIn("crm_notes.moved_tab_to", ["dispute"])
                        ->where('crm_notes.status', 1);
                })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
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
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            case 'paid':
                $model->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["paid"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
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
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',
                        // sale
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        // units
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        'users.name as user_name'
                    ]);

                break;
            default:
                $model->join('quality_notes', function ($join) {
                    $join->on('applicants.id', '=', 'quality_notes.applicant_id')
                        ->whereIn("quality_notes.moved_tab_to", ["cleared"])
                        ->where("quality_notes.status", 1);
                })
                    ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.office_id', '=', 'offices.id')
                    ->join('units', 'sales.unit_id', '=', 'units.id')
                    ->join('crm_notes', function ($join) {
                        $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
                            ->where('crm_notes.status', 1);
                    })
                    ->join('history', function ($join) {
                        $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                        $join->on('quality_notes.sale_id', '=', 'history.sale_id')
                            ->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
                            ->where("history.status", 1);
                    })
                    ->join('cv_notes', function ($join) {
                        $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                            ->whereColumn('cv_notes.sale_id', 'sales.id')
                            ->latest();
                    })
                    ->join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->addSelect([
                        'crm_notes.details as notes_detail',
                        'crm_notes.created_at as notes_created_at',
                        'offices.office_name as office_name',

                        /** sale */
                        'sales.id as sale_id',
                        'sales.job_category_id as sale_category_id',
                        'sales.job_title_id as sale_title_id',
                        'sales.sale_postcode',
                        'sales.job_type as sale_job_type',
                        'sales.timing',
                        'sales.salary',
                        'sales.experience as sale_experience',
                        'sales.qualification as sale_qualification',
                        'sales.benefits',
                        'sales.office_id as sale_office_id',
                        'sales.unit_id as sale_unit_id',
                        'sales.position_type',
                        'sales.status as sale_status',
                        'sales.created_at as sale_posted_date',

                        /** units */
                        'units.unit_name',
                        'units.unit_postcode',
                        'units.unit_website',

                        /** user */
                        'users.name as user_name'
                    ]);

                break;
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('applicants.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('applicants.updated_at', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sales.sale_postcode', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });
                    
                    $query->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('users.name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->where('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->where('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn("user_name", function ($applicant) {
                    return ucwords($applicant->user_name) ?? '-';
                })
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    if ($applicant->lat != null && $applicant->lng != null) {
                        $url = route('applicantsAvailableJobs', ['id' => $applicant->id, 'radius' => 15]);
                        $button = '<a href="' . $url . '" style="color:blue;">' . $applicant->formatted_postcode . '</a>'; // Using accessor
                    } else {
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('notes_detail', function ($applicant) {
                    $notes_detail = strip_tags($applicant->notes_detail); // avoid double escaping
                    $notes_created_at = Carbon::parse($applicant->notes_created_at)->format('d M Y, h:i A');
                    $notes = "<strong>Date: {$notes_created_at}</strong><br>{$notes_detail}";

                    $short = Str::limit($notes, 150);
                    $modalId = 'crm-' . $applicant->id;

                    $name = e($applicant->applicant_name);
                    $postcode = e($applicant->applicant_postcode);
                    $notesEscaped = nl2br(e($notes_detail));

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $modalId . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $modalId . '-label">Applicant\'s CRM Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Name:</strong> ' . $name . '</p>
                                        <p><strong>Postcode:</strong> ' . $postcode . '</p>
                                        <p><strong>Date:</strong> ' . $notes_created_at . '</p>
                                        <p class="notes-content"><strong>Notes Detail:</strong><br>' . $notesEscaped . '</p>
                                    </div>
                                     <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('updated_at', function ($applicant) {
                    return $applicant->formatted_updated_at; // Using accessor
                })
                ->addColumn('job_details', function ($applicant) {
                    $position_type = strtoupper(str_replace('-', ' ', $applicant->position_type));
                    $position = '<span class="badge bg-primary">' . htmlspecialchars($position_type, ENT_QUOTES) . '</span>';

                    if ($applicant->sale_status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->sale_status == 0 && $applicant->is_on_hold == 0) {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($applicant->sale_status == 2) {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($applicant->sale_status == 3) {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    // Escape HTML in $status for JavaScript (to prevent XSS)
                    $escapedStatus = htmlspecialchars($status, ENT_QUOTES);

                    // Prepare modal HTML for the "Job Details"
                    $modalHtml = $this->generateJobDetailsModal($applicant);

                    // Return the action link with a modal trigger and the modal HTML
                    return '<a href="#" class="dropdown-item" style="color: blue;" onclick="showDetailsModal('
                        . (int)$applicant->sale_id . ','
                        . '\'' . htmlspecialchars(Carbon::parse($applicant->sale_posted_date)->format('d M Y, h:i A'), ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->office_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->unit_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->sale_postcode, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->job_category_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->job_title_name, ENT_QUOTES) . '\','
                        . '\'' . $escapedStatus . '\','
                        . '\'' . htmlspecialchars($applicant->timing, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->sale_experience, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->salary, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($position, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->sale_qualification, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($applicant->benefits, ENT_QUOTES) . '\')">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                        </a>' . $modalHtml;
                })
                ->addColumn('action', function ($applicant) use ($tabFilter) {
                    $formattedMessage = '';
                    // Fetch SMS template from the database
                    $sms_template = SmsTemplate::where('title', 'crm_sent_cv')
                        ->where('status', 1)
                        ->first();

                    if ($sms_template) {
                        $sms_template = $sms_template->template;

                        $replace = [$applicant->applicant_name, $applicant->unit_name];
                        $prev_val = ['(applicant_name)', '(unit_name)'];

                        $newPhrase = str_replace($prev_val, $replace, $sms_template);
                        $formattedMessage = nl2br($newPhrase);
                    }
                    $html = '<div class="btn-group dropstart">
                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu">';

                    $actionButtons = '';
                    // Filter by status if it's not empty
                    switch ($tabFilter) {
                        case 'sent cvs':
                            $actionButtons = '
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#updateCrmNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        data-applicant-phone="' . $applicant->applicant_phone . '" 
                                        data-applicant-name="' . $applicant->applicant_name . '" 
                                        data-applicant-unit="' . $applicant->unit_name . '"
                                        onclick="updateCrmNotesModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\', \'' . htmlspecialchars($formattedMessage, ENT_QUOTES) . '\')">
                                        Add CRM Notes
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmSentCvToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmSentCvToRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                        Send Request
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRevertInQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                        Revert In Quality
                                    </a></li>
                                ';
                                if (!empty($applicant_msgs)) {
                                    if ($applicant_msgs['is_read'] == 0) {
                                        $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                    }
                                }
                            break;

                        case 'open cvs':
                            $actionButtons = '
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#updateCrmNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            data-applicant-phone="' . $applicant->applicant_phone . '" 
                                            data-applicant-name="' . $applicant->applicant_name . '" 
                                            data-applicant-unit="' . $applicant->unit_name . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="updateCrmNotesModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'open_cv\', \'' . htmlspecialchars($formattedMessage, ENT_QUOTES) . '\')">
                                            Add CRM Notes
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmSendRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmSendRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'open_cv\')">
                                            Send Request
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertInQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'open_cv\')">
                                            Revert In Quality
                                        </a></li>
                                    ';
                                if (!empty($applicant_msgs)) {
                                    if ($applicant_msgs['is_read'] == 0) {
                                        $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                    }
                                }
                            break;

                        case 'sent cvs (no job)':
                            $actionButtons = '
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#updateCrmNoJobNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="updateCrmNoJobNotesModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Add CRM Notes
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmSendNoJobRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmSendNoJobRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Send Request
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmSentCvNoJobRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmSentCvNoJobRevertInQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Quality
                                        </a></li>
                                    ';
                                if (!empty($applicant_msgs)) {
                                    if ($applicant_msgs['is_read'] == 0) {
                                        $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                    }
                                }
                            break;

                        case 'rejected cvs':
                            $actionButtons = '
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertRejectedCvToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertRejectedCvToSentCvModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Sent CV
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertRejectedCvToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertRejectedCvToQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                            Revert In Quality
                                        </a></li>
                                    ';
                                if (!empty($applicant_msgs)) {
                                    if ($applicant_msgs['is_read'] == 0) {
                                        $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                    }
                                }
                            break;

                        case 'request':
                            $emailIcon = '<i class="fas fa-envelope text-secondary" title="Not Sent"></i>';
                            $emailText = 'Not Sent';
                            $sentEmail = SentEmail::where('applicant_id', $applicant->id)
                                ->where('sale_id', $applicant->sale_id)
                                ->latest()->first();
                            
                            if ($sentEmail && $sentEmail->status == '1') {
                                $emailIcon = '<i class="fas fa-envelope text-success" title="Sent"></i>';
                                $emailText = 'Sent';
                            } elseif ($sentEmail && $sentEmail->status == '2') {
                                $emailIcon = '<i class="fas fa-envelope text-danger" title="Failed"></i>';
                                $emailText = 'Failed';
                            } elseif ($sentEmail && $sentEmail->status == '0') {
                                $emailIcon = '<i class="fas fa-envelope text-secondary" title="Pending"></i>';
                                $emailText = 'Pending';
                            }

                            $applicant_msgs = ApplicantMessage::where([
                                    'phone_number' => $applicant->applicant_phone, 
                                    'status' => 'incoming'
                                ])
                                ->orderBy('created_at', 'desc')->first();

                            $actionButtons = '<li><a class="dropdown-item" href="javascript:void(0)" >Email '. $emailText .'</a></li>';
                            if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 1) {
                                $actionButtons .= '<li><a href="javascript:void(0);" class="dropdown-item disabled text-danger">Already Scheduled</a></li>';
                            } else {
                                if ($sentEmail && $sentEmail->status == '1') {
                                    // If the email is successfully sent (status = '1'), skip the email modal
                                    $actionButtons .= '<li>
                                        <a href="#" class="dropdown-item" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmScheduleInterviewModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" 
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmScheduleInterviewModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Schedule Interview
                                        </a>
                                    </li>';
                                } else {
                                    // If no email has been sent yet, open the email modal first
                                    $actionButtons .= '<li>
                                        <a href="#" class="dropdown-item" 
                                            data-bs-toggle="modal"  
                                            data-bs-target="#crmSendApplicantEmailRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmSendApplicantEmailRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Schedule Interview
                                        </a>
                                    </li>';
                                }
                            }
                            $actionButtons .= '<li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertRequestedCvToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertRequestedCvToSentCvModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Sent CV
                                        </a></li>';
                            $actionButtons .= '<li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertRequestedCvToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertRequestedCvToQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                            Revert In Quality
                                        </a></li>';
                            $actionButtons .= '<li><a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmMoveToconfirmationModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmMoveToconfirmationModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Move to Confirmation
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" >Send SMS</a></li>';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;

                        case 'request (no job)':
                            $emailIcon = '<i class="fas fa-envelope text-secondary" title="Not Sent"></i>';
                            $emailText = 'Not Sent';
                            $sentEmail = SentEmail::where('applicant_id', $applicant->id)
                                ->where('sale_id', $applicant->sale_id)
                                ->latest()->first();
                            
                            if ($sentEmail && $sentEmail->status == '1') {
                                $emailIcon = '<i class="fas fa-envelope text-success" title="Sent"></i>';
                                $emailText = 'Sent';
                            } elseif ($sentEmail && $sentEmail->status == '2') {
                                $emailIcon = '<i class="fas fa-envelope text-danger" title="Failed"></i>';
                                $emailText = 'Failed';
                            } elseif ($sentEmail && $sentEmail->status == '0') {
                                $emailIcon = '<i class="fas fa-envelope text-secondary" title="Pending"></i>';
                                $emailText = 'Pending';
                            }

                            $applicant_msgs = ApplicantMessage::where([
                                    'phone_number' => $applicant->applicant_phone, 
                                    'status' => 'incoming'
                                ])
                                ->orderBy('created_at', 'desc')->first();

                            $actionButtons = '<li><a class="dropdown-item" href="javascript:void(0)" >Email '. $emailText .'</a></li>';
                            if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 1) {
                                $actionButtons .= '<li><a href="javascript:void(0);" class="dropdown-item disabled text-danger">Already Scheduled</a></li>';
                            } else {
                                if ($sentEmail && $sentEmail->status == '1') {
                                    // If the email is successfully sent (status = '1'), skip the email modal
                                    $actionButtons .= '<li>
                                        <a href="#" class="dropdown-item" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmScheduleInterviewModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" 
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmScheduleInterviewModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Schedule Interview
                                        </a>
                                    </li>';
                                } else {
                                    // If no email has been sent yet, open the email modal first
                                    $actionButtons .= '<li>
                                        <a href="#" class="dropdown-item" 
                                            data-bs-toggle="modal"  
                                            data-bs-target="#crmSendApplicantEmailRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmSendApplicantEmailRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Schedule Interview
                                        </a>
                                    </li>';
                                }
                            }
                            $actionButtons .= '<li><a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmMoveToconfirmationModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmMoveToconfirmationModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Move to Confirmation
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" >Send SMS</a></li>';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'rejected by request':
                            $actionButtons = '
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRejectRequestRevertToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRejectRequestRevertToSentCvModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Sent Cv
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRejectRequestRevertToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRejectRequestRevertToRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Request
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRejectRequestRevertToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRejectRequestRevertToQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Quality
                                    </a></li>
                                ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'confirmation':
                            $actionButtons .= '<li><a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmConfirmationAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmConfirmationAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Accept CV
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertConfirmationToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertConfirmationToRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Request
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                    ';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'rebook':
                            $actionButtons = '
                                        <li><a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRebookAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRebookAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Accept CV
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertRebookToConfirmationModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertRebookToConfirmationModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Confirmation
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                    ';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'attended to pre-start date':
                            $actionButtons = '
                                        <li><a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmAttendedPreStartDateAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmAttendedPreStartDateAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Accept CV
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertAttendToRebookModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertAttendToRebookModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Rebook
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                        <li><a class="dropdown-item" href="#" >Send Email</a></li>
                                    ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'declined':
                            $actionButtons = '
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertDeclinedToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertDeclinedToAttendedModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Attended
                                        </a></li>
                                    ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'not attended':
                            $actionButtons = '
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmRevertNotAttendedToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmRevertNotAttendedToAttendedModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Attended
                                        </a></li>
                                        <li><a class="dropdown-item" 
                                            href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#crmNotAttendedToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                            data-applicant-id="' . (int)$applicant->id . '"
                                            data-sale-id="' . (int)$applicant->sale_id . '"
                                            onclick="crmNotAttendedToQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                            Revert In Quality
                                        </a></li>
                                    ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'start date':
                            $actionButtons = '
                                    <li><a class="dropdown-item" href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmStartDateAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmStartDateAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Accept CV
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRevertStartDateToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRevertStartDateToAttendedModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Attended
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'start date hold':
                            $actionButtons = '
                                    <li><a class="dropdown-item" href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmStartDateHoldAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmStartDateHoldAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Add Note
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRevertStartDateHoldToStartDateModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRevertStartDateHoldToStartDateModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Start Date
                                    </a></li>
                                ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'invoice':
                            $actionButtons = '
                                    <li><a class="dropdown-item" href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmInvoiceAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmInvoiceAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Accept CV
                                    </a></li>
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRevertInvoiceToStartDateModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRevertInvoiceToStartDateModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Start Date
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'invoice sent':
                             $actionButtons = '
                                    <li><a class="dropdown-item" href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmInvoiceSentAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmInvoiceSentAcceptCVModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Accept CV
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" >Send SMS</a></li>
                                ';

                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'dispute':
                            $actionButtons = '
                                    <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#crmRevertDisputeToInvoiceModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        onclick="crmRevertDisputeToInvoiceModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                        Revert In Invoice
                                    </a></li>
                                    ';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        case 'paid':
                            $paid_status_button = ($applicant->paid_status == 'close') ? 'Open' : 'Close';
                            $actionButtons = '
                                <li><a class="dropdown-item" 
                                    href="#" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#crmChangePaidStatusModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                    data-applicant-id="' . (int)$applicant->id . '"
                                    data-sale-id="' . (int)$applicant->sale_id . '"
                                    onclick="crmChangePaidStatusModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ')">
                                   Mark As '. $paid_status_button . '
                                </a></li>
                                ';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                        default:
                            $actionButtons = '
                                <li><a class="dropdown-item" 
                                        href="#" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#updateCrmNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                        data-applicant-id="' . (int)$applicant->id . '"
                                        data-sale-id="' . (int)$applicant->sale_id . '"
                                        data-applicant-phone="' . $applicant->applicant_phone . '" 
                                        data-applicant-name="' . $applicant->applicant_name . '" 
                                        data-applicant-unit="' . $applicant->unit_name . '"
                                        onclick="updateCrmNotesModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\', \'' . htmlspecialchars($formattedMessage, ENT_QUOTES) . '\')">
                                        Add CRM Notes
                                    </a></li>
                                <li><a class="dropdown-item" 
                                    href="#" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#crmSentCvToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                    data-applicant-id="' . (int)$applicant->id . '"
                                    data-sale-id="' . (int)$applicant->sale_id . '"
                                    onclick="crmSentCvToRequestModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                    Send Request
                                </a></li>
                                <li><a class="dropdown-item" 
                                    href="#" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#crmRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"
                                    data-applicant-id="' . (int)$applicant->id . '"
                                    data-sale-id="' . (int)$applicant->sale_id . '"
                                    onclick="crmRevertInQualityModal(' . (int)$applicant->id . ', ' . (int)$applicant->sale_id . ', \'sent_cv\')">
                                    Revert In Quality
                                </a></li>
                            ';
                            if (!empty($applicant_msgs)) {
                                if ($applicant_msgs['is_read'] == 0) {
                                    $actionButtons .= '<li><a class="dropdown-item" href="#" >Reply SMS</a></li>';
                                }
                            }
                            break;
                    }

                    $html .= $actionButtons;  // Using the action buttons defined earlier
                    $url = route('crmNotesHistoryIndex', ['applicant_id' => (int)$applicant->id, 'sale_id' => (int)$applicant->sale_id]);
                    $html .= '<li><hr class="dropdown-divider"></li>';
                    // Common actions
                    $html .= '<li><a class="dropdown-item" target="_blank" href="' . $url . '">Notes History</a></li>';
                    $html .= '<li><a class="dropdown-item" href="#" onclick="viewManagerDetails(' . (int)$applicant->sale_unit_id . ')">Manager Details</a></li>';
                    $html .= '</ul></div>';

                    /*** Update CRM Notes Modal */
                    $html .= '<div id="updateCrmNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="updateCrmNotesModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="updateCrmNotesModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">Add CRM Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="updateCrmNotesForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="reasonDropdown' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Reason</label>
                                                <select class="form-select crm_select_reason" name="reason" id="reasonDropdown' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" required>
                                                    <option value="" disabled selected>Select Reason</option>
                                                    <option value="position_filled">Position Filled</option>
                                                    <option value="agency">Sent By Another Agency</option>
                                                    <option value="manager">Rejected By Manager</option>
                                                    <option value="no_response">No Response</option>
                                                    <option value="update_notes" selected>Update Notes</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a reason.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-danger crmSentCVRejectButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '" style="display:none">Reject</button>
                                                <button type="button" class="btn btn-primary saveUpdateCrmNotesButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '" >Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';


                    /** CRM Send Request Modal */
                    $html .= '<div id="crmSentCvToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmSentCvToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmSentCvToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Send Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmSendRequest') . '" method="POST" id="crmSendRequestForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="sendRequestDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmSendRequestButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';

                    /** CRM Revert In Quality Modal */
                    $html .= '<div id="crmRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertInQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertInQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert CV In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertInQuality') . '" method="POST" id="crmRevertInQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="revertInQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertInQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Revert Requested CV to Sent CV Modal */
                    $html .= '<div id="crmRevertRequestedCvToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertRequestedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertRequestedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Sent CV</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRequestedCvToSentCv') . '" method="POST" id="crmRevertRequestedCvToSentCvForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="RevertRevertRequestedCvToSentCvDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertRequestedCvToSentCvButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Revert Requested CV to Quality Modal */
                    $html .= '<div id="crmRevertRequestedCvToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertRequestedCvToQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertRequestedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRequestedCvToQuality') . '" method="POST" id="crmRevertRequestedCvToQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="RevertRevertRequestedCvToQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertRequestedCvToQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Revert Rejected CV to Sent CV Modal */
                    $html .= '<div id="crmRevertRejectedCvToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertRejectedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertRejectedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Sent CV</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRejectedCvToSentCv') . '" method="POST" id="crmRevertRejectedCvToSentCvForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="RevertRevertRejectedCvToSentCvDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertRejectedCvToSentCvButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Revert Rejected CV to Quality Modal */
                    $html .= '<div id="crmRevertRejectedCvToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertRejectedCvToQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertRejectedCvToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRejectedCvToQuality') . '" method="POST" id="crmRevertRejectedCvToQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="RevertRevertRejectedCvToQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertRejectedCvToQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /*** Schedule Interview Modal */
                    $title = 'request_configuration_email';
                    $newPhrase = '';
                    $newSubject = '';
                    $applicant_email = '';
                    $request_configuration_email = EmailTemplate::where('title', $title)->where('is_active', 1)->first();

                    if ($request_configuration_email) {
                        // Loop through each attribute of the model
                        foreach ($request_configuration_email->getAttributes() as $key => $value) {
                            if (is_string($value)) {
                                // Convert to UTF-8 if not properly encoded
                                $request_configuration_email->{$key} = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                            }
                        }

                        $applicant_name = $applicant->applicant_name;
                        $salary = $applicant->salary;
                        $job_postcode = $applicant->sale_postcode;
                        $unit_name = $applicant->unit_name;
                        $distance = $job_postcode;
                        $location = $job_postcode;

                        $job_title = $applicant->jobTitle ? $applicant->jobTitle->name : '-';

                        //replace variables into body
                        $data = $request_configuration_email->template;
                        $replace = [$applicant_name, strtoupper($job_title), $unit_name, $job_postcode, $salary, $distance, $location];
                        $prev_val = ['(applicant_name)', '(job_type)', '(unit_name)', '(postcode)', '(salary)', '(distance)', '(location)'];
                        $newPhrase = str_replace($prev_val, $replace, $data);

                        //replace variables into subjects
                        $subject = $request_configuration_email->subject;
                        $subjectReplace = [$job_title];
                        $prev_sub = ['(job_type)'];
                        $newSubject = str_replace($prev_sub, $subjectReplace, $subject);

                        $applicant_email = htmlspecialchars($applicant->applicant_email, ENT_QUOTES, 'UTF-8');
                        $newSubject = htmlspecialchars($newSubject, ENT_QUOTES, 'UTF-8');
                        $newPhrase = htmlspecialchars($newPhrase, ENT_QUOTES, 'UTF-8');
                    }
                    // If the email is successfully sent (status = '1'), skip the email modal
                    $html .= '<div id="crmScheduleInterviewModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmScheduleInterviewModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-md modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmScheduleInterviewModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">Schedule Interview for <em>' . $applicant->applicant_name . '</em></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '"></div>
                                        <form action="' . route('crmScheduleInterview') . '" method="POST" id="crmScheduleInterviewForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <!-- Date Picker Field -->
                                            <div class="mb-4">
                                                <label for="schedule_date' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Interview Date</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="icon-calendar5"></i></span>
                                                    <input type="date" class="form-control" 
                                                        name="schedule_date" 
                                                        id="schedule_date' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" 
                                                        placeholder="Select date"
                                                        data-date-format="Y-m-d"
                                                        data-min-date="today">
                                                    <div class="invalid-feedback">Please provide schedule date.</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Time Picker Field -->
                                            <div class="mb-4">
                                                <label for="schedule_time' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Interview Time</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="icon-watch2"></i></span>
                                                    <input type="time" class="form-control" 
                                                        name="schedule_time"
                                                        id="schedule_time' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" 
                                                        placeholder="Select time"
                                                        data-enable-time="true"
                                                        data-no-calendar="true"
                                                        data-time-format="H:i"
                                                        data-minute-increment="15">
                                                        <div class="invalid-feedback">Please provide time.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary saveCrmScheduleInterviewButton" 
                                                        data-applicant-id="' . (int)$applicant->id . '" 
                                                        data-sale-id="' . (int)$applicant->sale_id . '">Schedule</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';

                    // If no email has been sent yet, open the email modal first
                    $html .= '<div id="crmSendApplicantEmailRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="crmSendApplicantEmailRequestModalLabel" aria-hidden="true" style="z-index:99999">
                                <div class="modal-dialog modal-lg modal-dialog-top" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmSendApplicantEmailRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">Send Email To Applicant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRequestedInterviewEmailToApplicant') . '" method="POST" id="crmSendApplicantEmailRequestForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <div class="form-group">
                                                    <input type="email" name="email_address" id="email_address_requested_' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-control mt-2" placeholder="Enter Email Address" value="' . $applicant_email . '" required>
                                                    <div class="invalid-feedback">Please provide email address.</div>
                                                </div>
                                                <div class="form-group">
                                                    <input type="text" name="email_subject" id="email_subject_requested_' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-control mt-2" placeholder="Email Subject" value="' . $newSubject . '" required>
                                                    <div class="invalid-feedback">Please provide email subject.</div>
                                                </div>
                                                <div class="form-group">
                                                    <textarea name="email_body" id="email_body_requested_' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="10" class="form-control mt-2 summernote" placeholder="Email Body" required>' . $newPhrase . '</textarea>
                                                    <div class="invalid-feedback">Please provide email body.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmSendApplicantEmailRequestButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Submit Email</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';

                    /** CRM Move to Confirmation Modal */
                    $html .= '<div id="crmMoveToconfirmationModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmMoveToconfirmationModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmMoveToconfirmationModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Move To Confirmation Notes</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="" method="" id="crmMoveToconfirmationForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <input type="hidden" name="rejection_data" id="rejectionData' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" value="">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmMoveToconfirmationDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-danger savecrmMoveToconfirmationRejectButton" 
                                                        data-applicant-id="' . (int)$applicant->id . '" 
                                                        data-sale-id="' . (int)$applicant->sale_id . '">
                                                        Reject
                                                    </button>
                                                    <button type="button" class="btn btn-primary savecrmConfirmationButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Confirm</button>
                                                    <button type="button" class="btn btn-success savecrmConfirmationSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';

                    // Rejection Email Modal
                    $html .= '<div id="crmSendApplicantEmailOnRequestRejectModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmSendApplicantEmailOnRequestRejectModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmSendApplicantEmailOnRequestRejectModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">Send Rejection Email</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="notificationAlertReject' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form id="rejectEmailForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal" action="" method="POST">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <input type="hidden" name="notes" id="rejectionNotesHidden' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">To Email</label>
                                                <input type="email" name="to" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <input type="text" name="subject" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Message</label>
                                                <textarea name="body" class="form-control" rows="4" required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary saveCrmSendApplicantEmailRequestRejectButton">Send Email</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Confirmation Accept CV Modal */
                    $html .= '<div id="crmConfirmationAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmConfirmationAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmConfirmationAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Interview Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmConfirmationAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmConfirmationAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-warning crmConfirmationNotAttendButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Not Attend</button>
                                                <button type="button" class="btn btn-info crmConfirmationAttendButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Attend</button>
                                                <button type="button" class="btn btn-secondary crmConfirmationRebookButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Rebook</button>
                                                <button type="button" class="btn btn-primary crmConfirmationSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';

                    /*** Update CRM Notes Modal */
                    $html .= '<div id="updateCrmNoJobNotesModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="updateCrmNotesModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="updateCrmNoJobNotesModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">Add CRM Notes</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('updateCrmNoJobNotes') . '" method="POST" id="updateCrmNoJobNotesForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="noJobdetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="noJobdetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="reasonDropdownNoJob' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Reason</label>
                                                    <select class="form-select" name="reason" id="reasonDropdownNoJob' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" required>
                                                        <option value="" disabled selected>Select Reason</option>
                                                        <option value="position_filled">Position Filled</option>
                                                        <option value="agency">Sent By Another Agency</option>
                                                        <option value="manager">Rejected By Manager</option>
                                                        <option value="no_response">No Response</option>
                                                    </select>
                                                    <div class="invalid-feedback">Please select a reason.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveupdateCrmNoJobNotesButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '" >Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';


                    /** CRM Send Request Modal */
                    $html .= '<div id="crmSendNoJobRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmSendRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmSendNoJobRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Send Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmSendNoJobRequest') . '" method="POST" id="crmSendNoJobRequestForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="sendNoJobRequestDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="sendNoJobRequestDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmSendNoJobRequestButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';

                    /** CRM Revert In Quality Modal */
                    $html .= '<div id="crmSentCvNoJobRevertInQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmSentCvNoJobRevertInQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmSentCvNoJobRevertInQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert No Job CV In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmSentCvNoJobRevertInQuality') . '" method="POST" id="crmNoJobRevertInQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="revertNoJobInQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmNoJobRevertInQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Request Reject to Sent CV Modal */
                    $html .= '<div id="crmRejectRequestRevertToSentCvModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRejectRequestRevertToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRejectRequestRevertToSentCvModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Sent CV</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRequestRejectToSentCv') . '" method="POST" id="crmRevertToSentCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertToSentCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertToSentCVButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Request Reject to Request Modal */
                    $html .= '<div id="crmRejectRequestRevertToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRejectRequestRevertToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRejectRequestRevertToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRequestRejectToRequest') . '" method="POST" id="crmRevertToRequestForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertToRequestDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertToRequestButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Request Reject to Quality Modal */
                    $html .= '<div id="crmRejectRequestRevertToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRejectRequestRevertToQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRejectRequestRevertToQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRequestRejectToQuality') . '" method="POST" id="crmRevertToQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertToQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertToQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Confirmation to Request Modal */
                    $html .= '<div id="crmRevertConfirmationToRequestModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertConfirmationToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertConfirmationToRequestModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertConfirmToRequest') . '" method="POST" id="crmRevertConfirmationToRequestForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertConfirmationToRequestDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertConfirmationToRequestButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Not Attended to Attended Modal */
                    $html .= '<div id="crmRevertNotAttendedToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmNotAttendedToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmNotAttendedToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Attended</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmNotAttendedToAttended') . '" method="POST" id="crmNotAttendedToAttendedForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmNotAttendedToAttendedDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmNotAttendedToAttendedButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Not Attended to Quality Modal */
                    $html .= '<div id="crmNotAttendedToQualityModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmNotAttendedInQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmNotAttendedToQualityModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Quality</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmNotAttendedToQuality') . '" method="POST" id="crmNotAttendedToQualityForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmNotAttendedToQualityDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmNotAttendedToQualityButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Attended to pre-start date Accept CV Modal */
                    $html .= '<div id="crmAttendedPreStartDateAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmAttendedPreStartDateAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmAttendedPreStartDateAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Attended To Pre-Start Date Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmAttendedPreStartDateAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmAttendedPreStartDateAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-info crmAttendedToDeclineButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Decline</button>
                                                <button type="button" class="btn btn-primary crmAttendedToStartDateButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Start Date</button>
                                                <button type="button" class="btn btn-success crmAttendedSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Rebook Accept CV Modal */
                    $html .= '<div id="crmRebookAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRebookAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmRebookAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Rebook Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmRebookAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmRebookAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-info crmRebookToNotAttendButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Not Attend</button>
                                                <button type="button" class="btn btn-secondary crmRebookToAttendButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Attend</button>
                                                <button type="button" class="btn btn-primary crmRebookSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Revert Rebook to Confirmation Modal */
                    $html .= '<div id="crmRevertRebookToConfirmationModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertRebookToConfirmationModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertRebookToConfirmationModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Confirmation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertRebookToConfirmation') . '" method="POST" id="crmRevertRebookToConfirmationForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertRebookToConfirmationDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertRebookToConfirmationButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Rebook to Confirmation Modal */
                    $html .= '<div id="crmRevertAttendToRebookModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertAttendToRebookModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertAttendToRebookModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Rebook</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertAttendedToRebook') . '" method="POST" id="crmRevertAttendToRebookForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertAttendToRebookDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertAttendToRebookButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Revert Declined to Attended Modal */
                    $html .= '<div id="crmRevertDeclinedToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertDeclinedToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertDeclinedToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Attended</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertDeclinedToAttended') . '" method="POST" id="crmRevertDeclinedToAttendedForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertDeclinedToAttendedDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertDeclinedToAttendedButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Start Date Accept CV Modal */
                    $html .= '<div id="crmStartDateAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmStartDateAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmStartDateAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Start Date Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmStartDateAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmStartDateAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-info crmStartDateToInvoiceButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Invoice</button>
                                                <button type="button" class="btn btn-secondary crmStartDateToHoldButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Start Date Hold</button>
                                                <button type="button" class="btn btn-primary crmStartDateSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Revert Start Date to Attended Modal */
                    $html .= '<div id="crmRevertStartDateToAttendedModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertStartDateToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertStartDateToAttendedModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Attended</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertStartDateToAttended') . '" method="POST" id="crmRevertStartDateToAttendedForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertStartDateToAttendedDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertStartDateToAttendedButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Start Date Hold Accept CV Modal */
                    $html .= '<div id="crmStartDateHoldAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmStartDateHoldAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmStartDateHoldAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Start Date Hold Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmStartDateHoldAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmStartDateHoldAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary crmStartDateHoldSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Revert Start Date Hold to Start Date Modal */
                    $html .= '<div id="crmRevertStartDateHoldToStartDateModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertStartDateHoldToStartDateModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertStartDateHoldToStartDateModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Start Date</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertStartDateHoldToStartDate') . '" method="POST" id="crmRevertStartDateHoldToStartDateForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertStartDateHoldToStartDateDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertStartDateHoldToStartDateButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Start Date Hold Accept CV Modal */
                    $html .= '<div id="crmInvoiceAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmInvoiceAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmInvoiceAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Invoice Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmInvoiceAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmInvoiceAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-info crmInvoiceSendInvoiceButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Send Invoice</button>
                                                <button type="button" class="btn btn-secondary crmInvoiceDisputeButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Dispute</button>
                                                <button type="button" class="btn btn-primary crmInvoiceSaveButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Revert Invoice To Start Date Modal */
                    $html .= '<div id="crmRevertInvoiceToStartDateModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertInvoiceToStartDateModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertInvoiceToStartDateModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Start Date</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertInvoiceToStartDate') . '" method="POST" id="crmRevertInvoiceToStartDateForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertInvoiceToStartDateDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertInvoiceToStartDateButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Invoice Sent Accept CV Modal */
                    $html .= '<div id="crmInvoiceSentAcceptCVModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmInvoiceSentAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="crmInvoiceSentAcceptCVModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Invoice Sent Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                        <form action="" method="" id="crmInvoiceSentAcceptCVForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                            <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                            <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                            <div class="mb-3">
                                                <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                <textarea class="form-control" name="details" id="crmInvoiceSentAcceptCVDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                <div class="invalid-feedback">Please provide details.</div>
                                            </div>
                                            <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary crmInvoiceSentPaidButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Paid</button>
                                            <button type="button" class="btn btn-warning crmInvoiceSentDisputeButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Dispute</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    /** CRM Revert Dispute To Invoice Modal */
                    $html .= '<div id="crmRevertDisputeToInvoiceModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmRevertDisputeToInvoiceModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmRevertDisputeToInvoiceModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM Revert In Invoice</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmRevertDisputeToInvoice') . '" method="POST" id="crmRevertDisputeToInvoiceForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmRevertDisputeToInvoiceDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required></textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmRevertDisputeToInvoiceButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    /** CRM Change Paid Status Modal */
                    $paid_status_title = ($applicant->paid_status == 'close') ? 'Open' : 'Close';
                    $paid_status_timestamp = Carbon::parse($applicant->paid_timestamp);
                    $content_details = 'Applicant CV has been ' . ucwords($applicant->paid_status) . ' since ' 
                        . $paid_status_timestamp->format('d M Y') . ' (' 
                        . $paid_status_timestamp->diff(Carbon::now())->format('%y years, %m months and %d days') 
                        . '). Are you sure you want to ' . $paid_status_title . ' it?';

                    $html .= '<div id="crmChangePaidStatusModal' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="modal fade" tabindex="-1" aria-labelledby="crmChangePaidStatusModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-top">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="crmChangePaidStatusModalLabel' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '">CRM '. $paid_status_title .' To '. ucwords($applicant->applicant_name) .'\'s CV</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body modal-body-text-left">
                                            <div class="notificationAlert' . (int)$applicant->id . '-' . (int)$applicant->sale_id . ' notification-alert"></div>
                                            <form action="' . route('crmChangePaidStatus') . '" method="POST" id="crmChangePaidStatusForm' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-horizontal">
                                                <input type="hidden" name="applicant_id" value="' . (int)$applicant->id . '">
                                                <input type="hidden" name="sale_id" value="' . (int)$applicant->sale_id . '">
                                                <div class="mb-3">
                                                    <label for="details' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" class="form-label">Notes</label>
                                                    <textarea class="form-control" name="details" id="crmChangePaidStatusDetails' . (int)$applicant->id . '-' . (int)$applicant->sale_id . '" rows="4" required>'. $content_details .'</textarea>
                                                    <div class="invalid-feedback">Please provide details.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary saveCrmChangePaidStatusButton" data-applicant-id="' . (int)$applicant->id . '" data-sale-id="' . (int)$applicant->sale_id . '">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                    return $html;
                })
                ->rawColumns(['notes_detail', 'user_name', 'job_details', 'applicant_postcode', 'job_title', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }

    /** CRM Sent CV */
    public function updateCrmNotes(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
                'reason' => 'required'
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            if ($request->input('tab') == 'sent_cv') {
                // Private function might throw exceptions
                $this->crmSentSaveAction(
                    $request->input('applicant_id'),
                    $user->id,
                    $request->input('sale_id'),
                    $details,
                    $request->input('reason')
                );
            } elseif ($request->input('tab') == 'open_cv') {
                // Private function might throw exceptions
                $this->crmOpenCvAction(
                    $request->input('applicant_id'),
                    $user->id,
                    $request->input('sale_id'),
                    $details,
                    $request->input('reason')
                );
            }


            return response()->json(['success' => true, 'message' => 'CRM Notes Upated Successfully!']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmSendRequest(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Requested By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmSentRequestAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            if ($request->input('tab') == 'open_cv') {
                // Private function might throw exceptions
                $this->crmOpenCvSentRequestAction(
                    $request->input('applicant_id'),
                    $request->input('sale_id')
                );
            }

            return response()->json(['success' => true, 'message' => 'CRM Request Sent Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmSendRejectedCv(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
                'reason' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rejected By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmSentCVToRejectCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details,
                $request->input('reason')
            );

            if ($request->input('tab') == 'open_cv') {
                // Private function might throw exceptions
                $this->crmOpenCvSentRequestAction(
                    $request->input('applicant_id'),
                    $request->input('sale_id')
                );
            }

            return response()->json(['success' => true, 'message' => 'CRM Sent Rejected Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertInQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rejected By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRevertCVInQualityAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM CV Reverted In Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Rejected Cv */
    public function crmRevertRejectedCvToSentCv(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRevertRejectedCvToSentCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Reverted In Sent CV Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRejectedCvToQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $revertedInSentCv = $this->crmRevertRejectedCvToSentCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            if($revertedInSentCv){
                // Private function might throw exceptions
                $this->crmRevertCVInQualityAction(
                    $request->input('applicant_id'),
                    $user->id,
                    $request->input('sale_id'),
                    $details
                );
            }

            return response()->json(['success' => true, 'message' => 'CRM Reverted In Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRequestConfirm(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Confirmed By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRequestConfirmAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Request Confirmed Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRequestSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Requested By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRequestSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Request Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmScheduleInterview(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer|exists:applicants,id',
                'sale_id' => 'required|integer|exists:sales,id',
                'schedule_date' => 'required|date|after:today',
                'schedule_time' => 'required|date_format:H:i',
            ]);

            $user = Auth::user();
            
            DB::transaction();

            $interview = new Interview();
            $interview->user_id = $user->id;
            $interview->sale_id = $request->Input('sale_id');
            $interview->applicant_id = $request->Input('applicant_id');
            $interview->schedule_date = $request->Input('schedule_date');
            $interview->schedule_time = $request->Input('schedule_time');
            $interview->save();

            /** Update UID */
            $interview->interview_uid = md5($interview->id);
            $interview->save();

            return response()->json(['success' => true, 'message' => 'CRM Interview Scheduled Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
   

    /** CRM Sent No Job  */
    public function updateCrmNoJobNotes(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
                'reason' => 'required'
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmSentCvNoJobSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details,
                $request->input('reason')
            );

            return response()->json(['success' => true, 'message' => 'CRM No Job Notes Upated Successfully!']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmSendNoJobRequest(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
                'reason' => 'required'
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Requested By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmNoJobSentRequestAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details,
                $request->input('reason'),
            );

            return response()->json(['success' => true, 'message' => 'CRM No Job Request Sent Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmSendNoJobToRejectedCv(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
                'reason' => 'required'
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rejected By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmNoJobSentRejectCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details,
                $request->input('reason'),
            );

            return response()->json(['success' => true, 'message' => 'CRM No Job Rejected Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmSentCvNoJobRevertInQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmSentCvNoJobRevertCVInQualityAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Reverted In Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /** CRM Request Reject */
    public function crmRequestReject(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Request Rejected By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRequestRejectAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Request Rejected Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRequestRejectToSentCv(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $applicant_id = $request->input('applicant_id');
            $sale_id = $request->input('sale_id');

            $applicant = Applicant::find($applicant_id);
            $is_no_job = $applicant && $applicant->is_no_job == 1 ? true : false;
            
            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if($is_no_job){
                    // Private function might throw exceptions
                    $this->crmNoJobRequestRejectedRevertToSentCvAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );
                    
                }else{
                    if ($sent_cv_count < $sale->cv_limit) {
                        // Private function might throw exceptions
                        $this->crmRequestRejectedRevertToSentCvAction(
                            $request->input('applicant_id'),
                            $user->id,
                            $sale_id,
                            $details
                        );
                    }else{
                        return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'CRM Reverted To Sent CV Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRequestRejectToRequest(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');
            $applicant_id = $request->input('applicant_id');

            $applicant = Applicant::find($applicant_id);
            $is_no_job = $applicant && $applicant->is_no_job == 1 ? true : false;

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if($is_no_job){
                    // Private function might throw exceptions
                    $this->crmNoJobRequestRejectedRevertToRequestAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );
                    
                }else{
                    if ($sent_cv_count < $sale->cv_limit) {
                        // Private function might throw exceptions
                        $this->crmRequestRejectedRevertToRequestAction(
                            $request->input('applicant_id'),
                            $user->id,
                            $sale_id,
                            $details
                        );
                        
                    }else{
                        return response()->json(['success' => false, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                    }
                }

                return response()->json(['success' => true, 'message' => 'CV Reverted To Request Successfully']);

            }

            return response()->json(['success' => false, 'message' => 'Sale Not Found']);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRequestRejectToQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmRequestRejectedRevertToSentCvAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );
                    
                    /** Second revert in Quality */
                    $this->crmRevertCVInQualityAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $request->input('sale_id'),
                        $details
                    );

                }else{
                    return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => true, 'message' => 'CV Reverted To Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRequestedCvToSentCv(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRevertRequestToSentCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Reverted In Sent CV Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRequestedCvToQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $revertedInSentCv = $this->crmRevertRequestToSentCvAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            if($revertedInSentCv){
                // Private function might throw exceptions
                $this->crmRevertCVInQualityAction(
                    $request->input('applicant_id'),
                    $user->id,
                    $request->input('sale_id'),
                    $details
                );
            }

            return response()->json(['success' => true, 'message' => 'CRM Reverted In Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Confirmation */
    public function crmConfirmInterviewToNotAttend(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmInterviewNotAttendedAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Not Attend Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRequestedInterviewEmailToApplicant(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'email_address' => 'required|email',
            'email_subject' => 'required|max:255',
            'email_body' => 'required',
            'email_title' => 'required',
            'applicant_id' => 'required|integer',
            'sale_id' => 'required|integer',
        ]);

        try {
            $emailData = [
                'body' => $validatedData['email_body'],
                'subject' => $validatedData['email_subject'],
            ];

            // Send email using proper HTML email method
            // Mail::send([], [], function ($message) use ($validatedData) {
            //     $message->from('crm@kingsburypersonnel.com', 'Kingsbury Personnel Ltd')
            //         ->to($validatedData['email_address'])
            //         ->subject($validatedData['email_subject'])
            //         ->html($validatedData['email_body']); // Use html() instead of setBody()
            // });

            // Save email log
            $this->saveSentEmails(
                $validatedData['email_address'],
                '', // CC
                'crm@kingsburypersonnel.com',
                $validatedData['email_title'],
                $validatedData['email_subject'],
                $validatedData['email_body'],
                'Scheduled Interview',
                $validatedData['applicant_id'],
                $validatedData['sale_id']
            );

            return response()->json(['success' => true, 'message' => 'Email sent successfully']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertConfirmToRequest(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Request Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmConfirmationRevertToRequestAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Request Revert Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmConfirmInterviewToAttend(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Confirmed By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmInterviewAttendedAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Attend Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmConfirmSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Confirmed By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmInterviewSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmConfirmInterviewToRebook(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rebooked By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmInterviewRebookAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Rebook Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Rebook */
    public function crmRebookSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRebookSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Rebook Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRebookToNotAttended(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRebookToNotAttendedAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Not Attended Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRebookToAttended(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRebookToAttendedAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Interview Attended Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertRebookToConfirmation(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rebook Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRevertRebookToConfirmationAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Rebook Revert Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Attended */
    public function crmRevertAttendedToRebook(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Rebook Reverted By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmRevertAttendedToRebookAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Rebook Revert Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmAttendedToStartDate(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Started Date By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmAttendedToStartDateAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Start Date Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmAttendedToDecline(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Declined By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmAttendedToDeclineAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Decline Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmAttendedSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;

            // Private function might throw exceptions
            $this->crmPreStartDateAction(
                $request->input('applicant_id'),
                $user->id,
                $request->input('sale_id'),
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Started Date Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Not Attended */
    public function crmNotAttendedToAttended(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmInterviewNotAttendedToAttendedAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                }else{
                    return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => true, 'message' => 'CV Reverted To Attended Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmNotAttendedToQuality(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmInterviewNotAttendedToAttendedAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );
                    
                    /** Second revert in Quality */
                    // $this->crmRevertCVInQualityAction(
                    //     $request->input('applicant_id'),
                    //     $user->id,
                    //     $request->input('sale_id'),
                    //     $details
                    // );

                }else{
                    return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => true, 'message' => 'CV Reverted To Quality Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Decline */
    public function crmRevertDeclinedToAttended(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmRevertDeclineToAttendedAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                }else{
                    return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => true, 'message' => 'CV Reverted To Attended Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Start Date */
    public function crmRevertStartDateToAttended(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmRevertStartDateToAttendedAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CV Reverted To Attended Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmStartDateToInvoice(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Invoiced By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmStartDateToInvoiceAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                }else{
                    return response()->json(['error' => true, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => true, 'message' => 'CRM Invoice Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmStartDateToHold(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Start Date Hold By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmStartDateHoldAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Start Date Hold Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmStartDateSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmStartDateSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Start Date Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Start Date Hold*/
    public function crmRevertStartDateHoldToStartDate(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmRevertStartDateHoldToStartDateAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                    return response()->json(['success' => true, 'message' => 'CV Reverted To Start Date Successfully']);

                }else{
                    return response()->json(['success' => false, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => false, 'message' => 'Sale Not Found']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmStartDateHoldSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmStartDateHoldSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Start Date Hold Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Invoice*/
    public function crmSendInvoiceToInvoiceSent(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Invoice Sent By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmInvoiceToInvoiceSentAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Invoice Sent Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmInvoiceToDispute(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Disputed By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmInvoiceToDisputeAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Invoice To Dispute Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmRevertInvoiceToStartDate(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmRevertInvoiceToStartDateAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                    return response()->json(['success' => true, 'message' => 'CV Reverted To Start Date Successfully']);

                }else{
                    return response()->json(['success' => false, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => false, 'message' => 'Sale Not Found']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmInvoiceFinalSave(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Final By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmFinalSaveAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Invoice Final Saved Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /** CRM Invoice Sent */
    public function crmInvoiceSentToPaid(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Invoice Paid By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmInvoiceSentToPaidAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Invoice Paid Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmInvoiceSentToDispute(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Disputed By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            /** First revert in Sent CV */
            $this->crmInvoiceSentToDisputeAction(
                $request->input('applicant_id'),
                $user->id,
                $sale_id,
                $details
            );

            return response()->json(['success' => true, 'message' => 'CRM Invoice To Dispute Successfully']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Dispute */
    public function crmRevertDisputeToInvoice(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- Reverted By: ' . $user->name;
            $sale_id = $request->input('sale_id');

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = CvNote::where([
                    'sale_id' => $sale_id, 
                    'status' => 1
                    ])->count();

                if ($sent_cv_count < $sale->cv_limit) {
                    /** First revert in Sent CV */
                    $this->crmRevertDisputeToInvoiceAction(
                        $request->input('applicant_id'),
                        $user->id,
                        $sale_id,
                        $details
                    );

                    return response()->json(['success' => true, 'message' => 'CV Reverted To Start Date Successfully']);

                }else{
                    return response()->json(['success' => false, 'message' => 'Unable to proceed: You have reached the maximum number of CVs that can be submitted for this sale.']);
                }
            }

            return response()->json(['success' => false, 'message' => 'Sale Not Found']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /** CRM Paid */
    public function crmChangePaidStatus(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|integer',
                'sale_id' => 'required|integer',
                'details' => 'required',
            ]);

            $user = Auth::user();
            $details = $request->input('details') . ' --- By: ' . $user->name;
            $applicant_id = $request->input('applicant_id');
            $sale_id = $request->input('sale_id');

            $applicant = Applicant::find($applicant_id);

            if($applicant){
                $msg = '';
                if ($applicant->paid_status == 'open') {
                    $audit_data['action'] = "Close Applicant CV";
                    $update_paid_status = 'close';
                    $msg = 'Closed';
                } elseif ($applicant->paid_status == 'close') {
                    $audit_data['action'] = "Open Applicant CV";
                    $update_paid_status = 'open';
                    $msg = 'Opened';
                }
                $update_columns = ['paid_status' => $update_paid_status, 'paid_timestamp' => Carbon::now()];
                $applicant->update($update_columns);

                return response()->json(['success' => true, 'message' => 'CRM Changed Paid Status '. $msg .' Successfully']);

            }
            
            return response()->json(['success' => false, 'message' => 'Applicant Not Found']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function crmOpenToPaidApplicants()
    {
        $applicants = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.office_id', '=', 'offices.id')
            ->join('units', 'sales.unit_id', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sale_id', '=', 'history.sale_id');
            })
            ->where([
                'applicants.status' => 1,
                'applicants.paid_status' => 'close',
                'crm_notes.moved_tab_to' => 'paid',
                'history.sub_stage' => 'crm_paid',
                'history.status' => 1
            ])
            ->whereDate('applicants.paid_timestamp', '<', Carbon::now()->subMonths(5))
            ->whereDate('crm_notes.updated_at', '<', Carbon::now()->subMonths(5))
            ->orderBy("crm_notes.updated_at", "DESC")
            ->select('applicants.id') // reduce unnecessary fields
            ->get();

        $updatedCount = 0;

        if ($applicants->isNotEmpty()) {
            foreach ($applicants as $applicant) {
                $update_columns = [
                    'paid_status' => 'open',
                    'paid_timestamp' => Carbon::now(),
                ];

                $updated = Applicant::where('id', $applicant->id)->update($update_columns);
                if ($updated) {
                    $updatedCount++;
                    // Optional: Log audit if needed
                    // (new ActionObserver())->changeCvStatus($applicant->id, $update_columns, 'Opened');
                }
            }

            return response()->json([
                'success' => $updatedCount > 0,
                'message' => $updatedCount > 0 
                    ? "{$updatedCount} applicants marked as Open successfully."
                    : "No applicant status updated."
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No matching applicants found.'
        ]);
    }

    /** Get CRM Notes History */
    public function getApplicantCrmNotesHistoryAjaxRequest(Request $request)
    {
        $applicant_id = $request->applicant_id;
        $sale_id = $request->sale_id;

        // Prepare CRM Notes query
        $model = CrmNote::query()
            ->select('crm_notes.*')
            ->where('applicant_id', $applicant_id)
            ->where('sale_id', $sale_id);

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            } else {
                $model->orderBy('created_at', 'desc');
            }
        } else {
            $model->orderBy('created_at', 'desc');
        }

        // Apply search filter BEFORE sending to DataTables
        if ($request->has('search.value')) {
            $searchTerm = $request->input('search.value');
            $model->where(function ($query) use ($searchTerm) {
                $query->where('crm_notes.moved_tab_to', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('crm_notes.details', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Handle AJAX request
        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y, h:iA');
                })
                ->addColumn('moved_tab_to', function ($row) {
                    return '<span class="badge bg-primary">' . ucwords(str_replace('_', ' ', $row->moved_tab_to)) . '</span>';
                })
                ->addColumn('details', function ($row) {
                    $short = Str::limit(strip_tags($row->details), 250);
                    $full = e($row->details);
                    $id = 'exp-' . $row->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })
                ->rawColumns(['details', 'status', 'moved_tab_to'])
                ->make(true);
        }
    }

    /** Get CRM Notes History */
    public function getApplicantCrmNotes(Request $request)
    {
        try{
            $applicant_id = $request->applicant_id;
            $sale_id = $request->sale_id;

            // Prepare CRM Notes query
            $model = CrmNote::query()
                ->select('crm_notes.*')
                ->where('applicant_id', $applicant_id)
                ->where('sale_id', $sale_id)->latest()->get();

             // Check if the module note was found
            if (!$model) {
                return response()->json(['error' => 'CRM notes not found'], 404);  // Return 404 if not found
            }

            // Return the specific fields you need (e.g., applicant name, notes, etc.)
            return response()->json([
                'data' => $model,
                'success' => true
            ]);
        } catch (\Exception $e) {
            // If an error occurs, catch it and return a meaningful error message
            return response()->json([
                'error' => 'An unexpected error occurred. Please try again later.',
                'message' => $e->getMessage(),
                'success' => false
            ], 500); // Internal server error
        }
    }

    /************************ Private Functions ***************/
    private function generateJobDetailsModal($applicant)
    {
        $modalId = 'jobDetailsModal_' . $applicant->sale_id;  // Unique modal ID for each applicant's job details

        return '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="' . $modalId . 'Label">Job Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body modal-body-text-left">
                                <!-- Job details content will be dynamically inserted here -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>';
    }
    private function sendEmailToUnit($applicant_name, $service_name, $service_email)
    {
        $template = EmailTemplate::where('title', 'request_reject')->first();
        $data = $template->template;
        $replace = [$service_name, $applicant_name];
        $prev_val = ['(Service name)', '(xyz)'];

        $newPhrase = str_replace($prev_val, $replace, $data);

        $email_from = 'customerservice@kingsburypersonnel.com';
        $email_title = '';
        $email_subject = 'Application has been Withdraw';
        $action_name = 'Request Reject';
        $email_cc = '';

        Mail::send([], [], function ($message) use ($email_from, $newPhrase, $service_email, $email_subject) {
            $message->from($email_from, 'Kingsbury Personnel Ltd');
            $message->to($service_email);
            $message->subject($email_subject);
            $message->setBody($newPhrase, 'text/html');
        });
        if (Mail::failures()) {
             // Get failure details
            $failures = Mail::failures();

            // Convert array to a string message
            $errorMessage = "Failed to send email to the following address(es): " . implode(', ', $failures);
            return $errorMessage;
        }
            
        $dbSaveEmail = $this->saveSentEmails($service_email, $email_cc, $email_from, $email_title, $email_subject, $newPhrase, $action_name);
        
        return $dbSaveEmail;
    }
    private function saveSentEmails($email_to, $email_cc, $email_from, $email_title, $email_subject, $email_body, $action_name, $applicant_id = Null, $sale_id = Null)
    {
        $sent_email = new SentEmail();
        $sent_email->action_name = $action_name;
        $sent_email->sent_from = $email_from;
        $sent_email->sent_to = $email_to;
        $sent_email->cc_emails = $email_cc;
        $sent_email->subject = $email_subject;
        $sent_email->title = $email_title;
        $sent_email->template = $email_body;
        $sent_email->applicant_id = $applicant_id;
        $sent_email->sale_id = $sale_id;
        $sent_email->user_id = Auth::user()->id;

        if ($action_name == 'Random Email') {
            $sent_email->status = '0';
        }
        $sent_email->save();

        if($sent_email)
        {
            return true;
        }else{
            return false;
        }
    }

    /** No Job CRM Actions */
    
    private function crmNoJobRequestRejectedRevertToSentCvAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            // CvNote::where([
            //     'applicant_id' => $applicant_id,
            //     'sale_id' => $sale_id,
            //     'status' => 0
            // ])->orderBy('id', 'desc')  // Get the latest record
            //     ->limit(1)              // Only one record
            //     ->update(['status' => 1]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "moved_tab_to" => "cleared_no_job"
            ])->orderBy('id', 'desc')  // Get the latest record
                ->limit(1)
                ->update(["status" => 1]);

            CrmNote::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->whereIn("moved_tab_to", [
                    "cv_sent", 
                    "cv_sent_saved", 
                    "cv_sent_request",
                    "cv_sent_no_job"
                ])
                ->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_no_job";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'quality_cleared_no_job';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectedRevertToSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmNoJobRequestRejectedRevertToRequestAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            // CvNote::where([
            //     "applicant_id" => $applicant_id,
            //     "sale_id" => $sale_id,
            //     "status" => 0
            // ])
            // ->orderBy('id', 'desc')  // Get the latest record
            // ->limit(1)
            // ->update(["status" => 1]);

            /*** get latest sent cv record */
            $latest_sent_cv = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
                ])
                ->where("moved_tab_to", "cv_sent_no_job_request")
                ->latest()->first();

            $all_cv_sent_saved = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
                ])
                ->where("moved_tab_to", "cv_sent_saved")
                ->where('created_at', '>=', $latest_sent_cv->created_at)
                ->get();
                
            $crm_notes_ids[0] = $latest_sent_cv->id;
            foreach ($all_cv_sent_saved as $cv) {
                $crm_notes_ids[] = $cv->id;
            }

            CrmNote::whereIn('id', $crm_notes_ids)
                ->update(["status" => 1]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_no_job_request";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_no_job_request';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectedRevertToRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    /** No Job CRM Actions */

    /** CRM Sent Cv */
    private function crmSentSaveAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try {
            /** update to the existing active note of requested applicant_id and sale_id */
            CrmNote::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'status' => 1
            ])->update(['status' => 0]);

            // Create CRM note
            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_saved";
            $crm_notes->save();

            // Update UID
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            // Update history status
            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            // Create new history record
            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_save';
            $history->save();

            // Update history UID
            $history->history_uid = md5($history->id);
            $history->save();

            // Handle reject reason cases
            if (in_array($reject_reason, ['position_filled', 'agency', 'manager'])) {
                $sale = Sale::findOrFail($sale_id);

                if ($reject_reason == 'position_filled') {
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    // Just touch the record to update updated_at
                    $sale->touch();
                }

                // Create sale note
                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                // Update sale note UID
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmSentRequestAction($applicant_id, $user_id, $sale_id, $details)
    {
        try {
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_request' => true,
                    'is_interview_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            QualityNotes::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id, 
                "moved_tab_to" => "cleared",
                "status" => 1
            ])->update(["status" => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmSentRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmRevertCVInQualityAction($applicant_id, $user_id, $sale_id, $details)
    {
        try {
            Applicant::where("id", $applicant_id)->update([
                'is_interview_confirm' => false,
                'is_cv_in_quality_clear' => false,
                'is_cv_reject' => true,
                'is_cv_in_quality' => false
            ]);

            QualityNotes::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'moved_tab_to' => 'cleared',
                'status' => 1
            ])->update(['status' => 0]);

            $quality_notes = new QualityNotes();
            $quality_notes->applicant_id = $applicant_id;
            $quality_notes->user_id = $user_id;
            $quality_notes->sale_id = $sale_id;
            $quality_notes->details = $details;
            $quality_notes->moved_tab_to = "rejected";
            $quality_notes->save();

            /** Update UID */
            $quality_notes->quality_notes_uid = md5($quality_notes->id);
            $quality_notes->save();

            CvNote::where([
                'sale_id' => $sale_id,
                'applicant_id' => $applicant_id,
                'status' => 1
            ])->update(['status' => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                'status' => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'quality';
            $history->sub_stage = 'quality_reject';
            $history->save();

            /** Update UID */
            $history->history_uid = md5($history->id);
            $history->save();

            RevertStage::create([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'stage' => 'crm_revert',
                'user_id' => $user_id,
                'notes' => $details,
            ]);

            return true; // Indicate success

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmSentCVToRejectCvAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try{
            Applicant::where("id", $applicant_id)->update([
                'is_in_crm_reject' => true,
                'is_interview_confirm' => false
            ]);

            CrmNote::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'status' => 1
            ])->update(['status' => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_reject";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            $crm_rejected_cv = new CrmRejectedCv();
            $crm_rejected_cv->applicant_id = $applicant_id;
            $crm_rejected_cv->sale_id = $sale_id;
            $crm_rejected_cv->user_id = $user_id;
            $crm_rejected_cv->crm_note_id = $crm_notes->id;
            $crm_rejected_cv->reason = $reject_reason;
            $crm_rejected_cv->crm_rejected_cv_note = $details;
            $crm_rejected_cv->save();

            //update uid
            $crm_rejected_cv->crm_rejected_cv_uid = md5($crm_rejected_cv->id);
            $crm_rejected_cv->save();

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_reject';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            // Handle reject reason cases
            if (in_array($reject_reason, ['position_filled', 'agency', 'manager'])) {
                $sale = Sale::findOrFail($sale_id);

                if ($reject_reason == 'position_filled') {
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    // Just touch the record to update updated_at
                    $sale->touch();
                }

                // Create sale note
                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                // Update sale note UID
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }
        
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmSentRejectCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }

    /** CRM Rejected CV */
    private function crmRevertRejectedCvToSentCvAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            $crm_note_id = CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                'moved_tab_to' => 'cv_sent_reject'
            ])->select('id')->latest()->first()->id;

            CrmRejectedCv::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                'crm_note_id' => $crm_note_id,
                "status" => 1
            ])->update(["status" => 0]);

            CvNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 0
            ])
            ->update(["status" => 1]);

            QualityNotes::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 1
            ])
            ->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved", "cv_sent_reject"])
            ->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertRejectedToSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }

    /** CRM Sent No Job */
    private function crmSentCvNoJobRevertCVInQualityAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            $user_name = User::find($user_id);
            $details = request()->details . " --- Reverted By: " . $user_name->name;

            Applicant::where("id", $applicant_id)
            ->update([
                'is_interview_confirm' => false,
                'is_cv_in_quality_clear' => false,
                'is_cv_reject' => true,
                'is_cv_in_quality' => false
            ]);

            QualityNotes::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'moved_tab_to' => 'cleared',
                'status' => 1
            ])->update(['status' => 0]);

            $quality_notes = new QualityNotes();
            $quality_notes->applicant_id = $applicant_id;
            $quality_notes->user_id = $user_id;
            $quality_notes->sale_id = $sale_id;
            $quality_notes->details = $details;
            $quality_notes->moved_tab_to = "rejected";
            $quality_notes->save();

            /** Update UID */
            $quality_notes->quality_notes_uid = md5($quality_notes->id);
            $quality_notes->save();

            CvNote::where([
                'sale_id' => $sale_id,
                'applicant_id' => $applicant_id,
                'status' => 1
            ])->update(['status' => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                'status' => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'quality';
            $history->sub_stage = 'quality_no_job_reject';
            $history->save();

            /** Update UID */
            $history->history_uid = md5($history->id);
            $history->save();

            $details_revert = $details;

            RevertStage::create([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'stage' => 'crm_revert',
                'user_id' => $user_id,
                'notes' => $details_revert,
            ]);
        
            return true; // Indicate success

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmNoJobRevertCVInQualityAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmSentCvNoJobSaveAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_no_job";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'quality_cleared_no_job';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            if ($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager') {
                if ($reject_reason == 'position_filled') {
                    $sale = Sale::find($sale_id);
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    $sale = Sale::find($sale_id);
                    $sale->status = $sale->status;
                    $sale->update();
                }

                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                //update uid
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }
                            
            return true; // Indicate success

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmSentCvNoJobSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmNoJobSentRejectCvAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try{
            Applicant::where("id", $applicant_id)->update([
                'is_in_crm_reject' => true,
                'is_interview_confirm' => false
            ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_reject_no_job";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            $crm_rejected_cv = new CrmRejectedCv();
            $crm_rejected_cv->applicant_id = $applicant_id;
            $crm_rejected_cv->sale_id = $sale_id;
            $crm_rejected_cv->user_id = $user_id;
            $crm_rejected_cv->crm_note_id = $crm_notes->id;
            $crm_rejected_cv->reason = $reject_reason;
            $crm_rejected_cv->crm_rejected_cv_note = $details;
            $crm_rejected_cv->save();

            //update uid
            $crm_rejected_cv->crm_rejected_cv_uid = md5($crm_rejected_cv->id);
            $crm_rejected_cv->save();

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_no_job_reject';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            if ($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager') {
                if ($reject_reason == 'position_filled') {
                    $sale = Sale::find($sale_id);
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    $sale = Sale::find($sale_id);
                    $sale->status = $sale->status;
                    $sale->update();
                }

                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                //update uid
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }
                
            return true; // Indicate success

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmNoJobSentRejectCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmNoJobSentRequestAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_request' => true,
                    'is_interview_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_no_job_request";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "moved_tab_to" => "cleared_no_job",
                "status" => 1
            ])->update(["status" => 0]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_no_job_request';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            if ($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager') {
                if ($reject_reason == 'position_filled') {
                    $sale = Sale::find($sale_id);
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    $sale = Sale::find($sale_id);
                    $sale->status = $sale->status;
                    $sale->update();
                }

                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                //update uid
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }
                                    
            return true; // Indicate success

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmNoJobSentRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }

    /** CRM Open CV */
    private function crmOpenCvAction($applicant_id, $user_id, $sale_id, $details, $reject_reason)
    {
        try {
            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $cv_note = new CvNote();
            $cv_note->sale_id = $sale_id;
            $cv_note->user_id = $user_id;
            $cv_note->applicant_id = $applicant_id;
            $cv_note->details = $details;
            $cv_note->save();

            //update uid
            $cv_note->cv_uid = md5($cv_note->id);
            $cv_note->save();

            // Handle reject reason cases
            if (in_array($reject_reason, ['position_filled', 'agency', 'manager'])) {
                $sale = Sale::findOrFail($sale_id);

                if ($reject_reason == 'position_filled') {
                    $audit = new ActionObserver();
                    $audit->changeSaleStatus($sale, ['status' => 0]);
                    $sale->update(['status' => 0, 'is_on_hold' => false]);
                } else {
                    // Just touch the record to update updated_at
                    $sale->touch();
                }

                // Create sale note
                $sale_note = new SaleNote();
                $sale_note->sale_id = $sale_id;
                $sale_note->user_id = $user_id;
                $sale_note->sale_note = $details;
                $sale_note->save();

                // Update sale note UID
                $sale_note->sales_notes_uid = md5($sale_note->id);
                $sale_note->save();
            }

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmOpenCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmOpenCvSentRequestAction($applicant_id, $sale_id)
    {
        try {
            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "moved_tab_to" => "cv_hold",
                'status' => 1
            ])
            ->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "moved_tab_to" => "cleared",
                'status' => 0
            ])
            ->update(["status" => 1]);

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmOpenCvSentRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }

    /** CRM Request */
    private function crmRequestRejectAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_request_reject' => true,
                    'is_in_crm_request' => false
                ]);

            Interview::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(['status' => 0]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "request_reject";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request_reject';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            // $sale = Sale::where('id', $sale_id)
            //     ->select('head_office_unit')
            //     ->first();

            // $applicantRecord = Applicant::where("id", $applicant_id)
            //     ->select('applicant_name')
            //     ->first();

            // $applicant_name = $applicantRecord ? ucwords(strtolower($applicantRecord->applicant_name)) : '';

            // Mail::send([], [], function ($message) use ($email_body, $email_subject, $email_to) {
            //     $message->from('customerservice@kingsburypersonnel.com', 'Kingsbury Personnel Ltd');
            //     $message->to($email_to);
            //     $message->subject($email_subject);
            //     $message->setBody($email_body, 'text/html');
            // });

            // if (Mail::failures()) {
            //     // Get failure details
            //     $failures = Mail::failures();

            //     // Convert array to a string message
            //     $errorMessage = "Failed to send email to the following address(es): " . implode(', ', $failures);

            //     return $errorMessage;
            // } else {
            //     $email_from = 'customerservice@kingsburypersonnel.com';
            //     $email_sent_to_cc = '';
            //     $subject = $email_subject;
            //     $action_name = 'Request Reject';
            //     $this->saveSentEmails($email_to, $email_sent_to_cc, $email_from, $subject, $email_body, $action_name);
            //     //  return 'success';
            // }
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmRequestConfirmAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_request_confirm' => true,
                    'is_in_crm_request' => false,
                    'is_in_crm_request_reject' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "request_confirm";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request_confirm';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestConfirmAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmRequestSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "request_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmRevertRequestToSentCvAction($applicant_id, $user_id, $sale_id, $details)
    {
        try {
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_request' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            QualityNotes::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id, 
                "moved_tab_to" => "cleared",
                "status" => 0
            ])->update(["status" => 1]);

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertRequestToSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }

    /** CRM Request Reject */
    private function crmRequestRejectedRevertToSentCvAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CvNote::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'status' => 0
            ])
            ->orderBy('id', 'desc')  // Get the latest record
            ->limit(1)              // Only one record
            ->update(['status' => 1]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "moved_tab_to" => "cleared"
            ])
            ->orderBy('id', 'desc')  // Get the latest record
            ->limit(1)
            ->update(["status" => 1]);

            CrmNote::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])
                ->whereIn("moved_tab_to", [
                    "cv_sent", 
                    "cv_sent_saved", 
                    "cv_sent_request",
                    "request_reject"
                ])
                ->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectedRevertToSentCvAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmRequestRejectedRevertToRequestAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])
            ->orderBy('id', 'desc')  // Get the latest record
            ->limit(1)
            ->update(["status" => 1]);

            /*** get latest sent cv record */
            $latest_sent_cv = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
                ])
                ->where("moved_tab_to", "cv_sent")
                ->latest()->first();

            $all_cv_sent_saved = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
                ])
                ->where("moved_tab_to", "cv_sent_saved")
                ->where('created_at', '>=', $latest_sent_cv->created_at)
                ->get();
                
            $crm_notes_ids[0] = $latest_sent_cv->id;
            foreach ($all_cv_sent_saved as $cv) {
                $crm_notes_ids[] = $cv->id;
            }

            CrmNote::whereIn('id', $crm_notes_ids)
                ->update(["status" => 1]);

            // CrmNote::where([
            //     "applicant_id" => $applicant_id,
            //     "sale_id" => $sale_id,
            //     "status" => 1
            // ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRequestRejectedRevertToRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** CRM Confirmation */
    private function crmInterviewSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmInterviewSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmConfirmationRevertToRequestAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Interview::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'status' => 1
            ])->update(['status' => 0]);

            CrmNote::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id
            ])
            ->whereIn('moved_tab_to', [
                'cv_sent_request',
                'request_to_save',
                'request_to_confirm',
                'interview_save'
            ])->update(['status' => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmConfirmationRevertToRequestAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        } 
    }
    private function crmInterviewAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true,
                    'is_crm_request_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmInterviewAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmInterviewRebookAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true,
                    'is_crm_request_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "rebook";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_rebook';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmInterviewRebookAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
        
    }

    /** CRM Rebook */
    private function crmRevertRebookToConfirmationAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_request_confirm' => true,
                    'is_in_crm_request' => false,
                    'is_in_crm_request_reject' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "request_confirm";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_request_confirm';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertRebookToConfirmationAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmRebookSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "rebook_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_rebook_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRebookSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmRebookToAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true, 
                    'is_crm_request_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRebookToAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmRebookToNotAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => false, 
                    'is_crm_request_confirm' => false
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_not_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_not_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRebookToNotAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }

    /** CRM Attended */
    private function crmRevertAttendedToRebookAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true,
                    'is_crm_request_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1,
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "rebook";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_rebook';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertAttendedToRebookAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }
    }
    private function crmAttendedToStartDateAction($applicant_id, $user_id, $sale_id, $details)
    {
        Applicant::where("id", $applicant_id)
            ->update([
                'is_in_crm_start_date' => true,
                'is_crm_interview_attended' => 2 //pending
            ]);

        $crm_notes = new CrmNote();
        $crm_notes->applicant_id = $applicant_id;
        $crm_notes->user_id = $user_id;
        $crm_notes->sale_id = $sale_id;
        $crm_notes->details = $details;
        $crm_notes->moved_tab_to = "start_date";
        $crm_notes->save();

        //update uid
        $crm_notes->crm_notes_uid = md5($crm_notes->id);
        $crm_notes->save();

        History::where([
            "applicant_id" => $applicant_id,
            "sale_id" => $sale_id
        ])->update(["status" => 0]);

        $history = new History();
        $history->applicant_id = $applicant_id;
        $history->user_id = $user_id;
        $history->sale_id = $sale_id;
        $history->stage = 'crm';
        $history->sub_stage = 'crm_start_date';
        $history->save();

        //update uid
        $history->history_uid = md5($history->id);
        $history->save();
    }
    private function crmAttendedToDeclineAction($applicant_id, $user_id, $sale_id, $details)
    {
        Applicant::where("id", $applicant_id)
            ->update([
                'is_crm_request_confirm' => false,
                'is_crm_interview_attended' => 0 //not attended
            ]);

        CvNote::where([
            "applicant_id" => $applicant_id, 
            "sale_id" => $sale_id,
            "status" => 1
        ])->update(["status" => 0]);

        QualityNotes::where([
            "applicant_id" => $applicant_id, 
            "sale_id" => $sale_id,
            "status" => 1
        ])->update(["status" => 0]);

        CrmNote::where([
            "applicant_id" => $applicant_id,
            "sale_id" => $sale_id,
            "status" => 1
        ])->update(["status" => 0]);

        $crm_notes = new CrmNote();
        $crm_notes->applicant_id = $applicant_id;
        $crm_notes->user_id = $user_id;
        $crm_notes->sale_id = $sale_id;
        $crm_notes->details = $details;
        $crm_notes->moved_tab_to = "declined";
        $crm_notes->save();

        //update uid
        $crm_notes->crm_notes_uid = md5($crm_notes->id);
        $crm_notes->save();

        History::where([
            "applicant_id" => $applicant_id,
            "sale_id" => $sale_id
        ])->update(["status" => 0]);

        $history = new History();
        $history->applicant_id = $applicant_id;
        $history->user_id = $user_id;
        $history->sale_id = $sale_id;
        $history->stage = 'crm';
        $history->sub_stage = 'crm_declined';
        $history->save();

        //update uid
        $history->history_uid = md5($history->id);
        $history->save();
    }
    private function crmPreStartDateAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "prestart_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_prestart_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();

            return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmPreStartDateAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** Not Attended */
    private function crmInterviewNotAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => false,
                    'is_crm_request_confirm' => false
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_not_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_not_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
        
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmInterviewNotAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }
    private function crmInterviewNotAttendedToAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            /** update uid */
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmInterviewNotAttendedToAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }

    /** CRM Decline */
    private function crmRevertDeclineToAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            /*** latest sent cv records */
            $crm_notes_index = 0;
            $latest_sent_cv = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])->where("moved_tab_to", "cv_sent")
            ->latest()->first();

            $all_cv_sent_saved = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])
            ->where("moved_tab_to", "cv_sent_saved")
            ->where('created_at', '>=', $latest_sent_cv->created_at)
            ->get();

            $crm_notes_ids[$crm_notes_index++] = $latest_sent_cv->id;
            foreach ($all_cv_sent_saved as $cv) {
                $crm_notes_ids[$crm_notes_index++] = $cv->id;
            }

            /*** latest request records */
            $latest_request = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])->where("moved_tab_to", "cv_sent_request")
            ->latest()->first();

            $all_request_saved = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])
            ->where("moved_tab_to", "request_save")
            ->where('created_at', '>=', $latest_request->created_at)
            ->get();

            $crm_notes_ids[$crm_notes_index++] = $latest_request->id;
            foreach ($all_request_saved as $cv) {
                $crm_notes_ids[$crm_notes_index++] = $cv->id;
            }

            /*** latest confirmation records */
            $latest_confirmation = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])->where("moved_tab_to", "request_confirm")
            ->latest()->first();

            $all_confirmation_saved = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])
            ->where("moved_tab_to", "interview_save")
            ->where('created_at', '>=', $latest_confirmation->created_at)->get();

            $crm_notes_ids[$crm_notes_index++] = $latest_confirmation->id;
            foreach ($all_confirmation_saved as $cv) {
                $crm_notes_ids[$crm_notes_index++] = $cv->id;
            }

            /*** latest rebook records */
            $latest_rebook = CrmNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id
            ])->where("moved_tab_to", "rebook")
            ->latest()->first();

            if ($latest_rebook) {
                $all_rebook_saved = CrmNote::where([
                    "applicant_id" => $applicant_id, 
                    "sale_id" => $sale_id
                ])
                ->where("moved_tab_to", "rebook_save")
                ->where('created_at', '>=', $latest_rebook->created_at)->get();

                $crm_notes_ids[$crm_notes_index++] = $latest_rebook->id;
                foreach ($all_rebook_saved as $cv) {
                    $crm_notes_ids[$crm_notes_index++] = $cv->id;
                }
            }

            CrmNote::whereIn('id', $crm_notes_ids)->update(["status" => 1]);

            // CrmNote::where([
            //     "applicant_id" => $applicant_id,
            //     "sale_id" => $sale_id,
            //     "status" => 1
            // ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
        
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertDeclineToAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }

    /** CRM Start Date */
    private function crmStartDateToInvoiceAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_invoice' => true,
                    'is_in_crm_start_date' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "invoice";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_invoice';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmStartDateToInvoiceAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmStartDateHoldAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_start_date_hold' => true,
                    'is_in_crm_start_date' => false
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "start_date_hold";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_start_date_hold';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmStartDateHoldAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmStartDateSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "start_date_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_start_date_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmStartDateSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmRevertStartDateToAttendedAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            //if applicant revert from start date for purpose of send cv save or quality
            $lstHistory = History::where([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id,
                'sub_stage' => 'crm_start_date'
            ])
            ->whereDate('created_at', Carbon::now())
            ->latest()
            ->first();

            // Check if last history exists and was created within the last 10 minutes
            if ($lstHistory) {
                $timeDifference = Carbon::now()->diffInMinutes($lstHistory->created_at);

                if ($timeDifference <= 10) {
                    $lstHistory->delete();
                }
            }

            Applicant::where("id", $applicant_id)
                ->update([
                    'is_crm_interview_attended' => true, 
                    'is_crm_request_confirm' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_interview_attended';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
        
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertDeclineToAttendedAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }   
    }

    /** CRM Start Date Hold */
    private function crmRevertStartDateHoldToStartDateAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_start_date_hold' => false,
                    'is_in_crm_start_date' => true
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "start_date_back";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_start_date_back';

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertStartDateHoldToStartDateAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmStartDateHoldSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "start_date_hold_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_start_date_hold_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmStartDateHoldSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** CRM Invoice */
    private function crmRevertInvoiceToStartDateAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_start_date' => true, 
                    'is_crm_interview_attended' => 2,//pending 
                    'is_in_crm_invoice' => false
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "start_date";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_start_date';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertInvoiceToStartDateAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmInvoiceToDisputeAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_dispute' => true,
                    'is_in_crm_invoice' => false,
                    'is_in_crm_invoice_sent' => false,
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "dispute";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_dispute';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmDisputeAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmInvoiceToInvoiceSentAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_invoice' => false,
                    'is_in_crm_invoice_sent' => true,
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "invoice_sent";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_invoice_sent';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmDisputeAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmFinalSaveAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "final_save";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_final_save';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmFinalSaveAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** CRM Invoice Sent*/
    private function crmInvoiceSentToPaidAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_paid' => true,
                    'is_in_crm_invoice' => false,
                    'is_in_crm_invoice_sent' => false,
                    'paid_status' => 'close', 
                    'paid_timestamp' => Carbon::now()
                ]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "paid";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
            ])->update(["status" => 2]); //2 is paid

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_paid';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                        
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmPaidAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }
    private function crmInvoiceSentToDisputeAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            Applicant::where("id", $applicant_id)
                ->update([
                    'is_in_crm_dispute' => true,
                    'is_in_crm_invoice' => false,
                    'is_in_crm_invoice_sent' => false,
                ]);

            CvNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            QualityNotes::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "dispute";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_dispute';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                    
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmDisputeAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** Dispute */
    private function crmRevertDisputeToInvoiceAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CvNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])
            ->whereIn('moved_tab_to', [
                'cv_sent', 'cv_sent_saved', 
                'cv_sent_request', 'request_save', 
                'request_confirm', 'prestart_save', 
                'start_date', 'start_date_save', 
                'start_date_back', 'interview_attended', 
                'interview_save'
            ])->update(["status" => 1]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "invoice";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_invoice';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertDisputeToInvoiceAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

    /** Paid */
    private function crmRevertDisputeTosInvoiceAction($applicant_id, $user_id, $sale_id, $details)
    {
        try{
            CvNote::where([
                "applicant_id" => $applicant_id, 
                "sale_id" => $sale_id,
                "status" => 0
            ])->update(["status" => 1]);

            CrmNote::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 0
            ])
            ->whereIn('moved_tab_to', [
                'cv_sent', 'cv_sent_saved', 
                'cv_sent_request', 'request_save', 
                'request_confirm', 'prestart_save', 
                'start_date', 'start_date_save', 
                'start_date_back', 'interview_attended', 
                'interview_save'
            ])->update(["status" => 1]);

            $crm_notes = new CrmNote();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $user_id;
            $crm_notes->sale_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->moved_tab_to = "invoice";
            $crm_notes->save();

            //update uid
            $crm_notes->crm_notes_uid = md5($crm_notes->id);
            $crm_notes->save();

            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
                "status" => 1
            ])->update(["status" => 0]);

            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_invoice';
            $history->save();

            //update uid
            $history->history_uid = md5($history->id);
            $history->save();
                                            
           return true; // Indicate success

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error in crmRevertDisputeToInvoiceAction: " . $e->getMessage());

            // Re-throw the exception to be caught by the calling method
            throw $e;
        }  
    }

}
