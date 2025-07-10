<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Horsefly\Audit;
use Horsefly\JobTitle;
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


}
