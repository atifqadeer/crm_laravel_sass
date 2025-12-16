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
use Horsefly\User;
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
    public function sentEmailsIndex()
    {
        return view('emails.sent-emails');
    }
    public function writeMessageindex()
    {
        return view('messages.write-message');
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
        $job_type = $sale->position_type ?? '-';
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
            $sale->position_type ?? '-',
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
            $sale_id = $request->input('sale_id', null);
            
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
                    $is_save = $this->saveEmailDB($email, $email_from, $email_subject, $email_body, $email_title, $applicant->id, $sale_id);
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
            $from_email = $request->input('from_email') ?? 'info@kingsburypersonnel.com';

            if ($emailData != null){
                $dataEmail = explode(',',$emailData);

                $email_from = $from_email;
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
                    }else{
                        return response()->json(['success' => true, 'message' => 'Email saved successfully']);
                    }
                }
            }
        }catch (\Exception $e){
            return  response()->json(['success'=>false,'message'=>$e->getMessage()],422);
        }
    }

    /*************************************** */
    public function messageReceive(Request $request)
    {
        try {
            $phoneNumber_gsm = $request->input('phoneNumber');
            $phoneNumber = preg_replace('/^(\+44|44)/', '0', $phoneNumber_gsm);
            $message = $request->input('message');
            $msg_id = substr(md5(time()), 0, 14);
            $date_time = $request->input('time');
            $date_time_arr = explode(" ", $date_time);
            $date_res = $date_time_arr[0];
            $date = str_replace("/", "-", $date_res);
            $time = $date_time_arr[1];

            $data = [];

            $lastMessage = Message::where('phone_number', $phoneNumber)
                ->where('status', 'outgoing')
                ->latest()
                ->first();

            $applicant = Applicant::where('applicant_phone', $phoneNumber)->orWhere('applicant_landline', $phoneNumber)->first();
            $contact = Contact::where('contact_phone', $phoneNumber)->first();

            if($applicant){
                $data['module_id'] = $applicant->id;
                $data['module_type'] = 'Horsefly\Applicant';
            }elseif($contact){
                $data['module_id'] = $contact->contactable_id;
                $data['module_type'] = $contact->contactable_type;
            }else{
                $data['module_type'] = 'unknown';
            }
            
            if ($data) {
                $applicant_msg = new Message();
                $applicant_msg->module_id = $data['module_id'];
                $applicant_msg->module_type = $data['module_type'];
                $applicant_msg->user_id = $lastMessage ? $lastMessage->user_id : null;
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
    public function getChatBoxMessages(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'recipient_id' => 'required',
                'recipient_type' => 'required',
            ]);

            $recipientId = $request->input('recipient_id');
            $recipientType = $request->input('recipient_type');
            $moduleType = $recipientType === 'applicant' ? 'Horsefly\Applicant' : 'Horsefly\User';
            $recipient_id = '';
            $recipient_name = '';
            $recipient_phone = '';
            // Fetch recipient
            if($moduleType == Applicant::class){
                $recipient = Applicant::class::findOrFail($recipientId);
                $recipient_id = $recipient->id ?? '';
                $recipient_name = $recipient->applicant_name ?? '';
                $recipient_phone = $recipient->applicant_phone;
            }else{
                $recipient = User::class::findOrFail($recipientId);
                $recipient_id = $recipient->id ?? '';
                $recipient_name = $recipient->name ?? '';
            }

            Message::where('module_id', $recipientId)
                ->where('module_type', $moduleType)->update(['is_read' => 1]);

            // Fetch messages
            $query = Message::where('module_id', $recipientId)
                ->where('module_type', $moduleType)
                ->with('user')
                ->orderBy('created_at', 'asc');

            if ($request->list_ref == 'user-chat') {
                $query->where('user_id', Auth::id());
            }

            // $messages = $query->get()->map(function ($message) {
            //         return [
            //             'message' => $message->message,
            //             'created_at' => Carbon::parse($message->created_at)->format('d M Y, h:i A'),
            //             'is_sender' => $message->user_id == Auth::id(),
            //             'user_name' => $message->user ? $message->user->name : 'Unknown',
            //             'is_read' => $message->is_read ?? 0,
            //             'is_sent' => $message->is_sent ?? 0,
            //             'phone_number' => $message->phone_number,
            //             'status' => $message->status == 'outgoing' ? 'Sent' : 'Received',
            //         ];
            //     });

            // Fetch messages WITH pagination (IMPORTANT)
$messages = Message::where('module_id', $recipientId)
    ->where('module_type', $moduleType)
    ->with('user')
    ->orderByDesc('id') // newest first for pagination
    ->paginate(20);

// Transform messages
$formattedMessages = collect($messages->items())->map(function ($message) {
    return [
        'id' => $message->id,
        'message' => $message->message,
        'created_at' => Carbon::parse($message->created_at)->format('d M Y, h:i A'),
        'is_sender' => $message->user_id == Auth::id(),
        'user_name' => $message->user ? $message->user->name : 'Unknown',
        'is_read' => $message->is_read ?? 0,
        'is_sent' => $message->is_sent ?? 0,
        'phone_number' => $message->phone_number,
        'status' => $message->status === 'outgoing' ? 'Sent' : 'Received',
    ];
});


            return response()->json([
    'recipient' => [
        'id' => $recipient_id,
        'name' => $recipient_name,
        'phone' => $recipient_phone
    ],
    'messages' => $formattedMessages,
    'has_more' => $messages->hasMorePages(),
    'next_page' => $messages->currentPage() + 1,
]);

        } catch (\Exception $e) {
            Log::error('Error in getChatBoxMessages: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching messages'], 500);
        }
    }
    public function sendChatBoxMsg(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required',
            'recipient_type' => 'required',
            'recipient_phone' => 'required|string|max:50',
            'message' => 'required|string|max:255',
        ]);

        if($request->recipient_type == 'user')
        {
            $recipient_type = 'Horsefly\User';
        }else{
            $recipient_type = 'Horsefly\Applicant';
        }

        $message = new Message();
        $message->module_id = $request->recipient_id;
        $message->module_type = $recipient_type;
        $message->user_id = Auth::id();
        $message->msg_id = 'D' . mt_rand(1000000000000, 9999999999999);
        $message->message = $request->message;
        $message->phone_number = $request->recipient_phone;
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
            'phone_number' => $message->recipient_phone,
            'date' => $message->date,
            'time' => $message->time,
            'status' => $message->status,
            'is_sent' => $message->is_sent,
            'is_read' => $message->is_read,
            'is_sender' => true,
            'created_at' => date('H:i', strtotime($message->time)),
        ]);
    }
    // public function getApplicantsForMessage()
    // {
    //     $applicants = Applicant::with(['messages' => function ($query) {
    //         $query->latest()->take(1); // Get only the latest message
    //     }])
    //     ->withCount(['messages as unread_count' => function ($query) {
    //         $query->where('module_type', 'Horsefly\Applicant')
    //             ->where('is_read', 0);
    //     }])
    //     ->take(50)
    //     ->get()
    //     ->map(function ($applicant) {
    //         $lastMessage = $applicant->messages->first();

    //         return [
    //             'id' => $applicant->id,
    //             'name' => $applicant->applicant_name,
    //             'last_message' => $lastMessage ? [
    //                 'message' => Str::limit($lastMessage->message, 50),
    //                 'time' => Carbon::parse($lastMessage->time)->format('h:i A'),
    //                 'is_sent' => $lastMessage->is_sent ?? 0,
    //                 'is_read' => $lastMessage->is_read ?? 0,
    //                 'unread_count' => $applicant->unread_count,
    //             ] : null,
    //         ];
    //     });

    //     return response()->json($applicants);
    // }
    // public function getUserChats()
    // {
    //     try {
    //         // Get the authenticated user's ID
    //         $currentUserId = Auth::id();

    //         // Fetch users excluding the current user
    //         $users = User::where('id', '!=', $currentUserId)
    //             ->select('id', 'name')
    //             ->take(50)
    //             ->get()
    //             ->map(function ($user) use ($currentUserId) {
    //                 // Get the last message for this user (sent or received by the current user)
    //                 $lastMessage = Message::where(function ($query) use ($user, $currentUserId) {
    //                     $query->where('user_id', $currentUserId)
    //                         ->where('module_type', 'Horsefly\Applicant');
    //                 })->orWhere(function ($query) use ($user, $currentUserId) {
    //                     $query->where('user_id', $currentUserId)
    //                         ->where('module_type', 'Horsefly\Applicant');
    //                 })->orderBy('created_at', 'desc')
    //                 ->first();

    //                 // Get unread message count for this user
    //                 $unreadCount = Message::where('user_id', $currentUserId)
    //                     ->where('module_type', 'Horsefly\Applicant')
    //                     ->where('is_read', false)
    //                     ->count();

    //                 return [
    //                     'id' => $user->id,
    //                     'name' => $user->name,
    //                     'last_message' => $lastMessage ? [
    //                         'message' => $lastMessage->message,
    //                         'time' => $lastMessage->created_at->format('Y-m-d H:i:s'), // Adjust format as needed
    //                         'unread_count' => $unreadCount,
    //                     ] : null,
    //                 ];
    //             });

    //         return response()->json($users);
    //     } catch (\Exception $e) {
    //         // Log the error for debugging
    //         Log::error('Error fetching users for messages: ' . $e->getMessage());
    //         return response()->json(['error' => 'Failed to fetch users'], 500);
    //     }
    // }
    public function getApplicantsForMessage(Request $request)
    {
        $perPage = 20; // Fixed to 20 records per chunk
        $page = $request->input('page', 1); // Current page for pagination

        $applicants = Applicant::with(['messages' => function ($query) {
            $query->latest()->take(1); // Get only the latest message
        }])
        ->withCount(['messages as unread_count' => function ($query) {
            $query->where('module_type', 'Horsefly\Applicant')
                ->where('is_read', 0);
        }])
        ->orderBy('id', 'desc') // Consistent ordering for chats
        ->paginate($perPage);

        $applicants->getCollection()->transform(function ($applicant) {
            $lastMessage = $applicant->messages->first();

            return [
                'id' => $applicant->id,
                'name' => $applicant->applicant_name,
                'last_message' => $lastMessage ? [
                    'message' => Str::limit($lastMessage->message, 50),
                    'time' => Carbon::parse($lastMessage->time)->format('h:i A'),
                    'is_sent' => $lastMessage->is_sent ?? 0,
                    'is_read' => $lastMessage->is_read ?? 0,
                    'unread_count' => $applicant->unread_count,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $applicants->items(),
            'has_more' => $applicants->hasMorePages(),
            'next_page' => $applicants->currentPage() + 1,
        ]);
    }
    public function getUserChats(Request $request)
    {
        try {
            $currentUserId = Auth::id();
            $perPage = 20; // Fixed to 20 records per chunk
            $page = $request->input('page', 1); // Current page for pagination

            // Step 1: Get latest message ID per applicant sent by current user
            $latestMessageIds = DB::table('messages')
                ->select(DB::raw('MAX(id) as id'))
                ->where('user_id', $currentUserId)
                ->where('module_type', 'Horsefly\Applicant')
                ->whereNotNull('message')
                ->groupBy('module_id');

            // Step 2: Join to get full message and applicant
            $applicants = DB::table('messages')
                ->joinSub($latestMessageIds, 'latest_messages', function ($join) {
                    $join->on('messages.id', '=', 'latest_messages.id');
                })
                ->join('applicants', 'messages.module_id', '=', 'applicants.id')
                ->leftJoin(DB::raw('(SELECT module_id, COUNT(*) as unread_count 
                                    FROM messages 
                                    WHERE is_read = 0 
                                    AND module_type = "Horsefly\Applicant" 
                                    AND user_id = ' . $currentUserId . '
                                    GROUP BY module_id) as unread_msgs'),
                        'messages.module_id', '=', 'unread_msgs.module_id')
                ->select(
                    'applicants.id',
                    'applicants.applicant_name as name',
                    'messages.message',
                    'messages.created_at',
                    DB::raw('COALESCE(unread_msgs.unread_count, 0) as unread_count')
                )
                ->orderByDesc('messages.created_at')
                ->paginate($perPage);

            // Transform the collection to match frontend expectations
            $applicants->getCollection()->transform(function ($applicant) {
                return [
                    'id' => $applicant->id,
                    'name' => $applicant->name,
                    'last_message' => [
                        'message' => Str::limit($applicant->message, 50),
                        'time' => Carbon::parse($applicant->created_at)->format('h:i A'),
                        'unread_count' => $applicant->unread_count,
                        'applicant_name' => $applicant->name,
                    ],
                ];
            });

            return response()->json([
                'data' => $applicants->items(),
                'has_more' => $applicants->hasMorePages(),
                'next_page' => $applicants->currentPage() + 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching applicants for messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch applicants'], 500);
        }
    }
}
