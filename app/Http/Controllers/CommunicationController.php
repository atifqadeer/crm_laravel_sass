<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\Unit;
use Horsefly\Message;
use Horsefly\EmailTemplate;
use Horsefly\Applicant;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use Horsefly\JobCategory;
use Horsefly\SentEmail;
use App\Traits\SendEmails;
use App\Traits\SendSMS;
use Horsefly\Contact;
use Illuminate\Support\Str;

class CommunicationController extends Controller
{
    use SendEmails, SendSMS;

    public function __construct()   
    {
        //
    }
    public function index()
    {
        return view('emails.compose-email');
    }
    public function Messagesindex()
    {
        return view('messages.index');
    }
    public function writeMessageindex()
    {
        return view('messages.write');
    }
    public function sendEmailsToApplicants(Request $request)
	{
        $radius = 15; //kilometers
        $id = $request->sale_id;
        $sale = Sale::find($id);
        $unit = Unit::where('office_id', $sale->office_id)->first();
        $JobCategory = JobCategory::where('id', $sale->job_category_id)->first();
        $job_category = $sale->job_category_id;
        $job_postcode = $sale->sale_postcode;
        $job_title = $sale->job_title_id;
       
        $nearby_applicants = $this->distance($sale->lat, $sale->lng, $radius, $job_title, $job_category);
        $emails = is_null($nearby_applicants) ? '' : implode(',', $nearby_applicants->toArray());

        $user_name = Auth::user()->name;

        $category = ($JobCategory ? ucwords($JobCategory->name) : '-');
        $unit_name = ($unit ? $unit->unit_name : '-');
        $salary = $sale->salary ?? '-';
        $qualification = $sale->qualification ?? '-';
        $job_type = $sale->job_type ?? '-';
        $timing = $sale->timing ?? '-';
        $experience = $sale->experience ?? '-';
        $location = '-';

        // Fill template placeholders
        $replace = [
            $user_name,
            optional($JobCategory)->name ?? '-',
            optional($unit)->unit_name ?? '-',
            $sale->salary ?? '-',
            $sale->qualification ?? '-',
            $sale->job_type ?? '-',
            $sale->timing ?? '-',
            $sale->experience ?? '-',
            '-', 
        ];
        $prev_val = ['(agent_name)', '(job_category)', '(unit_name)', '(salary)', '(qualification)', '(job_type)', '(timing)', '(experience)', '(location)'];
        
        $formattedMessage = '';
        $subject = '';

        $template = EmailTemplate::where('slug','send_job_vacancy_details')->where('is_active', 1)->first();
        if($template && !empty($template->template)){
            $newPhrase = str_replace($prev_val, $replace, $template->template);
            $formattedMessage = nl2br($newPhrase);
            $subject = $template->subject;
        }

		return view('emails.send-email-to-applicant', compact('sale', 'unit', 'subject', 'formattedMessage', 'emails'));
    }
    function distance($lat, $lon, $radius, $job_title_id, $job_category_id)
    {
        $location_distance = Applicant::with('cv_notes')
            ->select(DB::raw("
                id,
                lat,
                lng,
                applicant_email,
                applicant_email_secondary,
                ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.852) AS distance
            "))
            ->having("distance", "<", $radius)
            ->orderBy("distance")
            ->where('applicants.status', 1)
            ->where('applicants.is_in_nurse_home', false)
            ->where('applicants.is_blocked', false)
            ->where('applicants.is_callback_enable', false)
            ->where('applicants.is_no_job', false)
            ->where("job_category_id", $job_category_id)
            ->orWhere("job_title_id", $job_title_id)
            ->whereNotNull('applicant_email')
            ->get();

        if ($location_distance->isEmpty()) {
            return null;
        }

        $validDomains = ['.com', '.msn', '.net', '.uk', '.gr'];

        $validEmailAddresses = $location_distance->flatMap(function ($applicant) use ($validDomains) {
            return collect([
                $applicant->applicant_email,
                $applicant->applicant_email_secondary
            ])
            ->filter() // Remove nulls
            ->unique() // Remove duplicates
            ->filter(function ($email) use ($validDomains) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
                if (preg_match('/^[A-Za-z0-9._%+-]+@example\.com$/', $email)) return false;
                if (strpos($email, '@') === false) return false;

                foreach ($validDomains as $domain) {
                    if (str_ends_with($email, $domain)) {
                        return true;
                    }
                }

                return false;
            });
        })->unique()->values();

        return $validEmailAddresses;
    }
    public function getSentEmailsAjaxRequest(Request $request)
    {
        $searchTerm = $request->input('search', ''); // This will get the search query

        $model = SentEmail::query();

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('sent_emails.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('sent_emails.updated_at', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('sent_emails.sent_to', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sent_emails.sent_from', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sent_emails.title', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sent_emails.cc_emails', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('sent_emails.subject', 'LIKE', "%{$searchTerm}%");
                });
            }
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('updated_at', function ($email) {
                    return Carbon::parse($email->updated_at)->format('d M Y, h:iA');
                })
                ->addColumn('action', function ($email) {
                    $sent_to = addslashes(htmlspecialchars($email->sent_to ?? ''));
                    $sent_from = addslashes(htmlspecialchars($email->sent_from ?? ''));
                    $title = addslashes(htmlspecialchars($email->title ?? ''));
                    $cc_email = addslashes(htmlspecialchars($email->cc_email ?? ''));
                    $subject = addslashes(htmlspecialchars($email->subject ?? ''));
                    
                    // Escape template safely (replace newlines and quotes)
                    $template = str_replace(["\r", "\n", "'"], [' ', ' ', "\\'"], $email->template ?? '');
                    $template = htmlspecialchars($template, ENT_QUOTES); // prevents XSS

                    return '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="showDetailsModal(
                                ' . $email->id . ',
                                \'' . $sent_to . '\',
                                \'' . $sent_from . '\',
                                \'' . $title . '\',
                                \'' . $cc_email . '\',
                                \'' . $subject . '\',
                                \'' . $template . '\'
                            )">
                            <i class=\"fas fa-eye\"></i> View
                        </button>
                    </div>';
                })

                ->rawColumns(['action','updated_at'])
                ->make(true);
        }
    }
    public function sendMessageToApplicant(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required',
                'message' => 'required',
            ]);

            $phone_numbers = explode(',',$request->input('phone_number'));
            $message = $request->input('message');
            $applicant_id = $request->input('applicant_id');

            foreach($phone_numbers as $phone){
                $applicant = Applicant::where('applicant_phone', $phone)->orWhere('applicant_landline')->first();
                
                if($applicant){
                    $is_saved = $this->saveSMSDB($phone, $message, 'Horsefly\Applicant', $applicant->id);
                }else{
                    $contact = Contact::where('contact_phone', $phone)->first();
                    if($contact){
                        $is_saved = $this->saveSMSDB($phone, $message, $contact->contactable_type, $contact->contactable_id);
                    }else{
                        $is_saved = $this->saveSMSDB($phone, $message, 'unknown', null);
                    }
                }

                if (!$is_saved) {
                    return response()->json([
                        'success' => false,
                        'message' => 'SMS saving failed.',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'SMS sent and stored successfully.',
            ]);

        } catch (ValidationException $ve) {
            // Validation errors will be caught separately
            return response()->json([
                'error' => $ve->validator->errors()->first(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // public function sendMessageToApplicant(Request $request)
    // {
    //     return $request->all();
    //     try {
    //         $phone_number = $request->input('phone_number');
    //         $message = $request->input('message');

    //         if (!$phone_number || !$message) {
    //             return response()->json([
    //                 'error' => 'Phone number and message are required.'
    //             ], 400);
    //         }

    //         // Encode message to be safely used in a URL
    //         $encoded_message = urlencode($message);

    //         $url = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='
    //             . $phone_number . '&message=' . $encoded_message . '&port=1&report=JSON&timeout=0';

    //         $curl = curl_init();
    //         curl_setopt_array($curl, [
    //             CURLOPT_URL => $url,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_HEADER => false,
    //             CURLOPT_TIMEOUT => 10,
    //         ]);

    //         $response = curl_exec($curl);
    //         $curlError = curl_error($curl);
    //         curl_close($curl);

    //         if ($response === false) {
    //             return response()->json([
    //                 'error' => 'Failed to connect to SMS API: ' . $curlError,
    //                 'query_string' => $url
    //             ], 500);
    //         }

    //         // Try to parse JSON response
    //         $parsed = json_decode($response, true);
    //         if (json_last_error() === JSON_ERROR_NONE) {
    //             $report = $parsed['result'] ?? null;
    //             $time = $parsed['time'] ?? null;
    //             $phone = $parsed['phonenumber'] ?? null;
    //         } else {
    //             // Fallback (non-JSON API response)
    //             $report = explode('"', strstr($response, "result"))[2] ?? null;
    //             $time = explode('"', strstr($response, "time"))[2] ?? null;
    //             $phone = explode('"', strstr($response, "phonenumber"))[2] ?? null;
    //         }

    //         if ($report === "success") {
    //             return response()->json([
    //                 'success' => 'SMS sent successfully!',
    //                 'data' => $response,
    //                 'phonenumber' => $phone,
    //                 'time' => $time,
    //                 'report' => $report
    //             ]);
    //         } elseif ($report === "sending") {
    //             return response()->json([
    //                 'success' => 'SMS is sending, please check later!',
    //                 'data' => $response,
    //                 'phonenumber' => $phone,
    //                 'time' => $time,
    //                 'report' => $report
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'error' => 'SMS failed, please check your device or settings!',
    //                 'data' => $response,
    //                 'report' => $report,
    //                 'query_string' => $url
    //             ]);
    //         }

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'An unexpected error occurred: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function sendRejectionEmail(Request $request)
    {
        try {
            

            return response()->json(['message' => 'Rejection email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }
    public function saveEmailsForApplicants(Request $request)
    {
        try {
            $emailData = $request->input('app_email');
            $template = EmailTemplate::where('slug','send_job_vacancy_details')->where('is_active', 1)->first();

            if ($emailData!=null && $template){
                $dataEmail = explode(',',$emailData);

                $email_from = $template->from_email;
                $email_subject = $request->input('email_subject');
                $email_body = $request->input('email_body');
                $email_title = $template->title;

                foreach($dataEmail as $email){
                    $applicant = Applicant::where('applicant_email', $email)->orWhere('applicant_email_secondary', $email)->first();
                    $is_save = $this->saveEmailDB($email, $email_from, $email_subject, $email_body, $email_title, $applicant->id);
                    if (!$is_save) {
                        // Optional: throw or log
                        Log::warning('Email saved to DB failed for applicant ID: ' . $applicant->id);
                        throw new \Exception('Email is not stored in DB');
                    }
                }
            }
            return response()->json(['success' => true, 'message' => 'Email saved successfully']);
        }catch (\Exception $e){
        return  response()->json(['status'=>false,'message'=>$e->getMessage()],422);
        }
    }
    public function saveComposedEmail(Request $request)
    {
        try {
            $emailData = $request->input('app_email');

            if ($emailData!=null){
                $dataEmail = explode(',',$emailData);

                $email_from = 'info@kingsburypersonnel.com';
                $email_subject = $request->input('email_subject');
                $email_body = $request->input('email_body');
                $email_title = $request->input('email_subject');

                foreach($dataEmail as $email){
                    $applicant = Applicant::where('applicant_email', $email)->orWhere('applicant_email_secondary', $email)->first();
                    if($applicant){
                        $is_save = $this->saveEmailDB($email, $email_from, $email_subject, $email_body, $email_title, $applicant->id);
                    }else{
                        $is_save = $this->saveEmailDB($email, $email_from, $email_subject, $email_body, $email_title, null);
                    }
                    if (!$is_save) {
                        // Optional: throw or log
                        Log::warning('Email saved to DB failed');
                        throw new \Exception('Email is not stored in DB');
                    }
                }
            }
            return response()->json(['success' => true, 'message' => 'Email saved successfully']);
        }catch (\Exception $e){
        return  response()->json(['status'=>false,'message'=>$e->getMessage()],422);
        }
    }

    /*************************************** */
    public function getMessages($applicantId)
    {
        $messages = Message::where('module_id', $applicantId)
            ->where('module_type', 'Horsefly\Applicant')
            ->with(['user', 'applicant'])
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'msg_id' => $message->msg_id,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user ? $message->user->name : 'Unknown',
                    'message' => $message->message,
                    'phone_number' => $message->phone_number,
                    'date' => $message->date,
                    'time' => $message->time,
                    'status' => $message->status,
                    'is_sent' => $message->is_sent,
                    'is_read' => $message->is_read,
                    'is_sender' => $message->user_id == Auth::id(),
                    'created_at' => date('H:i', strtotime($message->time)),
                ];
            }),
            'applicant' => Applicant::findOrFail($applicantId),
        ]);
    }
    public function sendMessage(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'message' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
        ]);

        $message = new Message();
        $message->applicant_id = $request->applicant_id;
        $message->user_id = Auth::id();
        $message->msg_id = 'D' . mt_rand(1000000000000, 9999999999999);
        $message->message = $request->message;
        $message->phone_number = $request->phone_number;
        $message->date = now()->toDateString();
        $message->time = now()->toTimeString();
        $message->status = 'outgoing';
        $message->save();

        return response()->json([
            'id' => $message->id,
            'msg_id' => $message->msg_id,
            'user_id' => $message->user_id,
            'user_name' => Auth::user()->name,
            'message' => $message->message,
            'phone_number' => $message->phone_number,
            'date' => $message->date,
            'time' => $message->time,
            'status' => $message->status,
            'is_sent' => $message->is_sent,
            'is_read' => $message->is_read,
            'is_sender' => true,
            'created_at' => date('H:i', strtotime($message->time)),
        ]);
    }
    public function getApplicantsForMessage()
    {
        $applicants = Applicant::with(['messages' => function ($query) {
            $query->where('user_id', Auth::id())
                  ->orWhereIn('phone_number', function ($subQuery) {
                      $subQuery->select('phone_number')
                               ->from('messages')
                               ->where('user_id', Auth::id());
                  })
                  ->latest()
                  ->first();
        }])->get();

        return response()->json(
            $applicants->map(function ($applicant) {
                $lastMessage = $applicant->messages->first();
                return [
                    'id' => $applicant->id,
                    'name' => $applicant->name,
                    'last_message' => $lastMessage ? [
                        'message' => $lastMessage->message,
                        'time' => $lastMessage->time,
                        'is_sent' => $lastMessage->is_sent,
                        'is_read' => $lastMessage->is_read,
                        'unread_count' => Message::where('module_id', $applicant->id)
                            ->where('module_type', 'Horsefly\Applicant')
                            ->where('is_read', 0)
                            ->where('user_id', '!=', Auth::id())
                            ->count(),
                    ] : null,
                ];
            })
        );
    }
    public function messageReceive(Request $request)
    {
        try {
            $phoneNumber_gsm = $request->input('phoneNumber');
            $phoneNumber = str_replace("+44", "0", $phoneNumber_gsm);
            $message = $request->input('message');
            $msg_id = substr(md5(time()), 0, 14);
            $date_time = $request->input('time');
            $date_time_arr = explode(" ", $date_time);
            $date_res = $date_time_arr[0];
            $date = str_replace("/", "-", $date_res);
            $time = $date_time_arr[1];

            $data = [];

            $applicant = Applicant::where('applicant_phone', $phoneNumber)->first();
            if($applicant){
                $data['module_id'] = $applicant->id;
                $data['module_type'] = 'Horsefly\Applicant';
            }else{
                $contact = Contact::where('contact_phone', $phoneNumber)->first();
                $data['module_id'] = $contact->contactable_id;
                $data['module_type'] = $contact->contactable_type;
            }
            
            if ($data) {
                $applicant_msg = new Message();
                $applicant_msg->module_id = $data['module_id'];
                $applicant_msg->module_type = $data['module_type'];
                $applicant_msg->user_id = '1';
                $applicant_msg->msg_id = $msg_id;
                $applicant_msg->message = $message;
                $applicant_msg->phone_number = $phoneNumber;
                $applicant_msg->date = $date;
                $applicant_msg->time = $time;
                $applicant_msg->status = 'incoming';
                $applicant_msg->save();

                return response()->json(['message' => 'Message received and saved successfully.']);
            } else {
                return response()->json(['message' => 'Phone number not found in Applicant'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to save message: ' . $e->getMessage()], 500);
        }
    }
}
