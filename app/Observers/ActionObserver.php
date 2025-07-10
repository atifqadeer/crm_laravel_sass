<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Horsefly\Audit;
use Horsefly\Applicant;
use Carbon\Carbon;

class ActionObserver
{
    public function changeSaleStatus($sale, $columns)
    {
        $auth_user = Auth::user();

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $columns;
        $d_message = '';
        $message = '';

        if ($columns['status'] == 0) {
            $d_message = 'closed';
            $message = 'sale-closed';
        } elseif ($columns['status'] == 1) {
            $d_message = 'opened';
            $message = 'sale-opened';
        } elseif ($columns['status'] == 2) {
            $d_message = 'pending';
            $message = 'sale-pending';
        } elseif ($columns['status'] == 3) {
            $d_message = 'rejected';
            $message = 'sale-rejected';
        }
        $data['message'] = 'Sale ('.$sale->postcode.' - '.$sale->job_title.') '.$d_message;

        // Create the audit log entry
        $sale->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_encode(array_merge(json_decode($sale->toJson(), true), $data)),
            "message" => $message,
        ]);
    }
    public function changeSaleOnHoldStatus($sale, $columns)
    {
        $auth_user = Auth::user();

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $columns;
        $d_message = '';
        $message = '';

        if ($columns['status'] == '1') {
            $d_message = 'on hold';
            $message = 'sale-on-hold';
        } elseif ($columns['status'] == '0') {
            $d_message = 'un hold';
            $message = 'sale-un-hold';
        } elseif ($columns['status'] == '2') {
            $d_message = 'pending on hold';
            $message = 'sale-pending-on-hold';
        }
        $data['message'] = 'Sale ('. $sale->postcode .' - '. $sale->job_title .') '. $d_message;

        // Create the audit log entry
        $sale->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_encode(array_merge(json_decode($sale->toJson(), true), $data)),
            "message" => $message,
        ]);
    }
    public function changeCvStatus($applicant_id, $columns, $msg)
    {
        $auth_user = Auth::user();

        $data = [
            'action_performed_by' => $auth_user->name,
            'changes_made' => $columns,
        ];

        $audit = new Audit();
        $audit->user_id = $auth_user->id;
        $audit->data = $data; // ✅ This is an array
        $audit->message = 'Applicant CV ' . $msg;
        $audit->auditable_id = $applicant_id;
        $audit->auditable_type = \Horsefly\Applicant::class;
        $audit->save();
    }
    public function customApplicantAudit($applicant, $column)
    {
        $auth_user = Auth::user();

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $column;
        $d_message = '';
        $message = '';

        if($column == 'applicant_notes'){
            $d_message = 'note updated';
            $message = "Applicant '".ucwords($applicant->applicant_name)."' notes has been updated";
        }

        $data['message'] = "Applicant '".ucwords($applicant->applicant_name)."' ".$d_message;

        // Create the audit log entry
        $applicant->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_encode(array_merge(json_decode($applicant->toJson(), true), $data)),
            "message" => $message,
        ]);
    }
    public function customOfficeAudit($office, $column)
    {
        $auth_user = Auth::user();

        $data['action_performed_by'] = $auth_user->name;
        $data['changes_made'] = $column;
        $d_message = '';
        $message = '';

        if($column == 'office_notes'){
            $d_message = 'note updated';
            $message = "Head Office '".ucwords($office->office_name)."' notes has been updated";
        }

        $data['message'] = "Head Office '".ucwords($office->office_name)."' ".$d_message;

        // Create the audit log entry
        $office->audits()->create([
            "user_id" => Auth::id(),
            "data" => json_encode(array_merge(json_decode($office->toJson(), true), $data)),
            "message" => $message,
        ]);
    }
}
