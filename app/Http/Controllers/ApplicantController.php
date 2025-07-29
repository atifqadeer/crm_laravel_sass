<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\Applicant;
use Horsefly\ApplicantNote;
use Horsefly\ModuleNote;
use Horsefly\EmailTemplate;
use Horsefly\SmsTemplate;
use Horsefly\SentEmail;
use Horsefly\JobTitle;
use Horsefly\JobSource;
use Horsefly\CVNote;
use Horsefly\History;
use Horsefly\JobCategory;
use Horsefly\ApplicantPivotSale;
use Horsefly\NotesForRangeApplicant;
use App\Exports\ApplicantsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Horsefly\Mail\GenericEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use App\Observers\ActionObserver;
use App\Traits\SendEmails;
use App\Traits\SendSMS;
use App\Traits\Geocode;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use League\Csv\Reader;

class ApplicantController extends Controller
{
    use SendEmails, SendSMS, Geocode;

    public function __construct()
    {
        //
    }
    /**
     * Display a listing of the applicants.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('applicants.list');
    }
    public function create()
    {
        $jobSources = JobSource::all();
        $jobCategories = JobCategory::all();
        $jobTitles = JobTitle::all();
        return view('applicants.create', compact('jobSources', 'jobCategories', 'jobTitles'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_category_id' => 'required|exists:job_categories,id',
            'job_type' => ['required', Rule::in(['specialist', 'regular'])],
            'job_title_id' => 'required|exists:job_titles,id',
            'job_source_id' => 'required|exists:job_sources,id',
            'applicant_name' => 'required|string|max:255',
            'gender' => 'required',
            'applicant_email' => 'required|email|max:255|unique:applicants,applicant_email',
            'applicant_email_secondary' => 'nullable|email|max:255|unique:applicants,applicant_email_secondary',
            'applicant_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'applicant_phone' => 'required|string|max:20|unique:applicants,applicant_phone',
            'applicant_landline' => 'nullable|string|max:20|unique:applicants,applicant_landline',
            'applicant_experience' => 'nullable|string',
            'applicant_notes' => 'required|string|max:255',
            'applicant_cv' => 'nullable|mimes:docx,doc,csv,pdf|max:5000',
        ]);

        $validator->sometimes('have_nursing_home_experience', 'required|boolean', function ($input) {
            $nurseCategory = JobCategory::where('name', 'nurse')->first();
            return $nurseCategory && $input->job_category_id == $nurseCategory->id;
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Please fix the errors in the form'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $applicantData = $request->only([
                'job_category_id',
                'job_type',
                'job_title_id',
                'job_source_id',
                'applicant_name',
                'applicant_email',
                'applicant_email_secondary',
                'applicant_postcode',
                'applicant_phone',
                'applicant_landline',
                'applicant_experience',
                'applicant_notes',
                'have_nursing_home_experience',
                'gender',
            ]);

            $applicantData['applicant_phone'] = preg_replace('/[^0-9]/', '', $applicantData['applicant_phone']);
            $applicantData['applicant_landline'] = $applicantData['applicant_landline']
                ? preg_replace('/[^0-9]/', '', $applicantData['applicant_landline'])
                : null;
            $applicantData['user_id'] = Auth::id();

            $applicantData['applicant_notes'] = $applicant_notes = $request->applicant_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            if ($request->hasFile('applicant_cv')) {
                $filenameWithExt = $request->file('applicant_cv')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('applicant_cv')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $path = $request->file('applicant_cv')->storeAs('uploads/resume/', $fileNameToStore, 'public');
                $applicantData['applicant_cv'] = $path;
            }

            $postcode = $request->applicant_postcode;
            $postcode_query = strlen($postcode) < 6
                ? DB::table('outcodepostcodes')->where('outcode', $postcode)->first()
                : DB::table('postcodes')->where('postcode', $postcode)->first();

            if (!$postcode_query) {
                try {
                    $result = $this->geocode($postcode);

                    // If geocode fails, throw
                    if (!isset($result['lat']) || !isset($result['lng'])) {
                        throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                    }

                    $applicantData['lat'] = $result['lat'];
                    $applicantData['lng'] = $result['lng'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to locate address: ' . $e->getMessage()
                    ], 400);
                }
            } else {
                $applicantData['lat'] = $postcode_query->lat;
                $applicantData['lng'] = $postcode_query->lng;
            }

            // ✅ Validate lat/lng presence before inserting
            if (empty($applicantData['lat']) || empty($applicantData['lng'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Postcode location is required. Please provide a valid postcode.'
                ], 400);
            }

            $applicant = Applicant::create($applicantData);
            $applicant->update(['applicant_uid' => md5($applicant->id)]);

            // Create new module note
            $moduleNote = ModuleNote::create([
                'details' => $applicant_notes,
                'module_noteable_id' => $applicant->id,
                'module_noteable_type' => 'Horsefly\Applicant',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            $jobCategory = JobCategory::find($request->job_category_id);
            $jobCategoryName = $jobCategory ? $jobCategory->name : '';

            /** Send Email */
            $email_template = EmailTemplate::where('slug', 'applicant_welcome_email')
                ->where('is_active', 1)
                ->first();

            if ($email_template && !empty($email_template->template)) {
                $email_to = $applicant->applicant_email;
                $email_from = $email_template->from_email;
                $email_subject = $email_template->subject;
                $email_body = $email_template->template;
                $email_title = $email_template->title;

                $replace = [$applicant->applicant_name, 'an Online Portal', $jobCategoryName];
                $prev_val = ['(applicant_name)', '(website_name)', '(job_category)'];

                $newPhrase = str_replace($prev_val, $replace, $email_body);
                $formattedMessage = nl2br($newPhrase);

                // Attempt to send email
                $is_save = $this->saveEmailDB($email_to, $email_from, $email_subject, $formattedMessage, $email_title, $applicant->id);
                if (!$is_save) {
                    // Optional: throw or log
                    Log::warning('Email saved to DB failed for applicant ID: ' . $applicant->id);
                    throw new \Exception('Email is not stored in DB');
                }
            }

            // Fetch SMS template from the database
            $sms_template = SmsTemplate::where('slug', 'applicant_welcome_sms')
                ->where('status', 1)
                ->first();

            if ($sms_template && !empty($sms_template->template)) {
                $sms_to = $applicant->applicant_phone;
                $sms_template = $sms_template->template;

                $replace = [$applicant->applicant_name, 'an Online Portal', $jobCategoryName];
                $prev_val = ['(applicant_name)', '(website_name)', '(job_category)'];

                $newPhrase = str_replace($prev_val, $replace, $sms_template);
                $formattedMessage = nl2br($newPhrase);

                $is_save = $this->saveSMSDB($sms_to, $formattedMessage, $applicant->id);
                if (!$is_save) {
                    // Optional: throw or log
                    Log::warning('SMS saved to DB failed for applicant ID: ' . $applicant->id);
                    throw new \Exception('SMS is not stored in DB');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Applicant created successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating applicant: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,xlsx|max:50480',
        ]);

        ini_set('max_execution_time', 1500);
        ini_set('memory_limit', '512M');

        try {
            // Initialize logging
            Log::channel('daily')->debug('Starting CSV/XLSX import process.');

            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename, 'local');
            $filePath = storage_path("app/{$path}");
            Log::channel('daily')->debug("File stored at: {$filePath}");

            // Preload data
            $jobCategories = JobCategory::pluck('id', 'name')->mapKeys(fn($key) => strtolower($key))->toArray();
            $jobTitles = JobTitle::select('id', 'name', 'job_category_id', 'type')
                ->get()
                ->groupBy('job_category_id')
                ->map(fn($titles) => $titles->pluck('type', 'id')->mapKeys(fn($key) => strtolower($key))->toArray())
                ->toArray();
            $jobSources = JobSource::pluck('id', 'name')->mapKeys(fn($key) => strtolower(preg_replace('/\s+/', ' ', trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $key)))))->toArray();

            $processedData = [];
            $failedRows = [];
            $rowIndex = 1;

            // Determine file type and process
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension === 'csv') {
                // Process CSV
                $content = file_get_contents($filePath);
                $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
                if ($encoding === false) {
                    Log::channel('daily')->error("Failed to detect encoding for CSV: {$filePath}");
                    throw new \Exception('Unable to detect CSV encoding.');
                }
                if ($encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    file_put_contents($filePath, $content);
                    Log::channel('daily')->debug("Converted CSV to UTF-8 from {$encoding}");
                }

                $csv = Reader::createFromPath($filePath, 'r');
                $csv->setHeaderOffset(0);
                $csv->setDelimiter(',');
                $csv->setEnclosure('"');
                $csv->setEscape('\\');

                $headers = $csv->getHeader();
                $records = $csv->getRecords();
            } elseif ($extension === 'xlsx') {
                // Process XLSX
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                $headers = array_shift($rows); // First row is header
                $records = new \ArrayIterator(array_map(fn($row) => array_combine($headers, $row), $rows));
            } else {
                Log::channel('daily')->error("Unsupported file extension: {$extension}");
                throw new \Exception('Unsupported file type.');
            }

            $expectedColumnCount = count($headers);
            Log::channel('daily')->debug('Headers: ' . json_encode($headers) . ", Count: {$expectedColumnCount}");

            foreach ($records as $row) {
                $rowIndex++;
                Log::channel('daily')->debug("Processing row {$rowIndex}: " . json_encode($row));

                // Validate required fields
                if (empty($row['job_category']) || empty($row['applicant_job_title'])) {
                    Log::channel('daily')->warning("Row {$rowIndex}: Missing job_category or applicant_job_title");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Missing job_category or applicant_job_title'];
                    continue;
                }

                // Ensure row matches header count
                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false) {
                    Log::channel('daily')->warning("Row {$rowIndex}: Header mismatch");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Header mismatch'];
                    continue;
                }

                // Sanitize row data
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\p{L}\p{N}\s.,-]/u', '', $value);
                    }
                    return $value ?: null; // Convert empty strings to null
                }, $row);

                // Parse dates
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $paid_timestamp = !empty($row['paid_timestamp'])
                        ? Carbon::parse($row['paid_timestamp'])->format('Y-m-d H:i:s')
                        : null;
                } catch (\Exception $e) {
                    Log::channel('daily')->error("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $failedRows[] = ['row' => $rowIndex, 'error' => "Invalid date format: {$e->getMessage()}"];
                    continue;
                }

                // Handle postcode and geolocation
                $cleanPostcode = '0';
                if (!empty($row['applicant_postcode'])) {
                    preg_match('/[A-Z]{1,2}[0-9]{1,2}\s*[0-9][A-Z]{2}/i', $row['applicant_postcode'], $matches);
                    $cleanPostcode = $matches[0] ?? substr(trim($row['applicant_postcode']), 0, 8);
                }
                $lat = is_numeric($row['lat']) ? (float) $row['lat'] : 0.0;
                $lng = is_numeric($row['lng']) ? (float) $row['lng'] : 0.0;
                if ($lat == 0.0 && $lng == 0.0 && $cleanPostcode !== '0') {
                    $postcodeQuery = strlen($cleanPostcode) < 6
                        ? DB::table('outcodepostcodes')->where('outcode', $cleanPostcode)->first()
                        : DB::table('postcodes')->where('postcode', $cleanPostcode)->first();
                    if ($postcodeQuery) {
                        $lat = $postcodeQuery->lat;
                        $lng = $postcodeQuery->lng;
                    } else {
                        Log::channel('daily')->warning("Row {$rowIndex}: No geolocation data for postcode {$cleanPostcode}");
                    }
                }

                // Handle job category and title
                $job_category_id = null;
                $job_title_id = null;
                $job_type = '';
                $category_map = [
                    'nurse' => 'nurse specialist',
                    'non-nurse' => 'nonnurse specialist',
                ];
                $requested_category = strtolower(trim($row['job_category']));
                $specialist_title = $category_map[$requested_category] ?? null;
                if ($specialist_title) {
                    $job_category_name = $requested_category === 'non-nurse' ? 'non nurse' : $requested_category;
                    $job_category_id = $jobCategories[$job_category_name] ?? null;
                    if ($job_category_id) {
                        $requested_job_title = strtolower(trim($row['applicant_job_title']));
                        $titles = $jobTitles[$job_category_id] ?? [];
                        $job_title_id = array_key_first(array_filter($titles, fn($_, $id) => $id === $requested_job_title, ARRAY_FILTER_USE_BOTH));
                        $job_type = $job_title_id ? $titles[$job_title_id] : '';
                        if (!$job_title_id) {
                            Log::channel('daily')->warning("Row {$rowIndex}: Job title not found: {$requested_job_title} for category ID: {$job_category_id}");
                            $failedRows[] = ['row' => $rowIndex, 'error' => "Job title not found: {$requested_job_title}"];
                            continue;
                        }
                    } else {
                        Log::channel('daily')->warning("Row {$rowIndex}: Job category not found: {$job_category_name}");
                        $failedRows[] = ['row' => $rowIndex, 'error' => "Job category not found: {$job_category_name}"];
                        continue;
                    }
                } else {
                    Log::channel('daily')->warning("Row {$rowIndex}: Invalid job category: {$requested_category}");
                    $failedRows[] = ['row' => $rowIndex, 'error' => "Invalid job category: {$requested_category}"];
                    continue;
                }

                // Handle job source
                $sourceRaw = $row['applicant_source'] ?? '';
                $cleanedSource = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $sourceRaw))));
                $firstTwoWordsSource = implode(' ', array_slice(explode(' ', $cleanedSource), 0, 2));
                $jobSourceId = $jobSources[$firstTwoWordsSource] ?? 2; // Default to Reed (ID 2)

                // Handle applicant name and landline
                $rawName = $row['applicant_name'] ?? '';
                $cleanedName = preg_replace('/\s+/', ' ', trim($rawName));
                preg_match('/\d{10,}/', $cleanedName, $matches);
                $extractedNumber = $matches[0] ?? null;
                $cleanedNumber = $extractedNumber ? preg_replace('/\D/', '', $extractedNumber) : null;
                $applicantLandline = $row['applicant_landline'] ?? null;
                if (!$applicantLandline && $cleanedNumber) {
                    $applicantLandline = $cleanedNumber;
                }
                $finalName = trim(preg_replace('/\d+/', '', $cleanedName)) ?: null;

                // Handle applicant home phone
                $rawLandline = $row['applicant_homePhone'] ?? '';
                $digitsOnly = preg_replace('/\D/', '', $rawLandline);
                $startsWithHyphen = ltrim($rawLandline) && ltrim($rawLandline)[0] === '-';
                $homePhone = null;
                if (strlen($digitsOnly) === 11) {
                    $homePhone = $digitsOnly;
                } elseif (strlen($digitsOnly) === 10 && ($startsWithHyphen || ($digitsOnly && $digitsOnly[0] === '7'))) {
                    $homePhone = '0' . $digitsOnly;
                }

                // Handle applicant phone
                $rawPhone = $row['applicant_phone'] ?? '';
                $parts = array_map('trim', explode('/', $rawPhone));
                $firstRaw = $parts[0] ?? '';
                $secondRaw = $parts[1] ?? '';
                $normalizePhone = function ($input) {
                    $digits = preg_replace('/\D/', '', $input);
                    $startsWithHyphen = ltrim($input) && ltrim($input)[0] === '-';
                    if (strlen($digits) === 11) {
                        return $digits;
                    } elseif (strlen($digits) === 10 && ($startsWithHyphen || ($digits && $digits[0] === '7'))) {
                        return '0' . $digits;
                    }
                    return null;
                };
                $phone = $normalizePhone($firstRaw);
                $landlinePhone = $normalizePhone($secondRaw);

                // Handle is_crm_interview_attended
                $is_crm_interview_attended = match (strtolower($row['is_crm_interview_attended'] ?? '')) {
                    'yes' => 1,
                    'pending' => 2,
                    'no' => 0,
                    default => null,
                };

                // Link to office (if office_id is provided)
                $office = null;
                if (!empty($row['office_id'])) {
                    $office = Office::find($row['office_id']);
                    if (!$office) {
                        Log::channel('daily')->warning("Row {$rowIndex}: Office ID {$row['office_id']} not found");
                        $failedRows[] = ['row' => $rowIndex, 'error' => "Office ID {$row['office_id']} not found"];
                        continue;
                    }
                }

                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'applicant_uid' => $row['id'] ? md5($row['id']) : null,
                    'user_id' => $row['applicant_user_id'] ?? null,
                    'applicant_name' => $finalName,
                    'applicant_email' => $row['applicant_email'] ?? null,
                    'applicant_notes' => $row['applicant_notes'] ?? null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'applicant_cv' => $row['applicant_cv'] ?? null,
                    'updated_cv' => $row['updated_cv'] ?? null,
                    'applicant_postcode' => $cleanPostcode,
                    'applicant_experience' => $row['applicant_experience'] ?? null,
                    'job_category_id' => $job_category_id,
                    'job_source_id' => $jobSourceId,
                    'job_title_id' => $job_title_id,
                    'job_type' => $job_type,
                    'applicant_phone' => $phone,
                    'applicant_landline' => $homePhone ?? $landlinePhone ?? $applicantLandline,
                    'is_blocked' => $row['is_blocked'] ?? 0,
                    'is_no_job' => ($row['is_no_job'] ?? 0) == '1' ? 1 : 0,
                    'is_temp_not_interested' => $row['temp_not_interested'] ?? 0,
                    'is_no_response' => $row['no_response'] ?? 0,
                    'is_circuit_busy' => $row['is_circuit_busy'] ?? 0,
                    'is_callback_enable' => ($row['is_callback_enable'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_nurse_home' => ($row['is_in_nurse_home'] ?? '') == 'yes' ? 1 : 0,
                    'is_cv_in_quality' => ($row['is_cv_in_quality'] ?? '') == 'yes' ? 1 : 0,
                    'is_cv_in_quality_clear' => ($row['is_cv_in_quality_clear'] ?? '') == 'yes' ? 1 : 0,
                    'is_cv_sent' => ($row['is_CV_sent'] ?? '') == 'yes' ? 1 : 0,
                    'is_cv_reject' => ($row['is_CV_reject'] ?? '') == 'yes' ? 1 : 0,
                    'is_interview_confirm' => ($row['is_interview_confirm'] ?? '') == 'yes' ? 1 : 0,
                    'is_interview_attend' => ($row['is_interview_attend'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_request' => ($row['is_in_crm_request'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_reject' => ($row['is_in_crm_reject'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_request_reject' => ($row['is_in_crm_request_reject'] ?? '') == 'yes' ? 1 : 0,
                    'is_crm_request_confirm' => ($row['is_crm_request_confirm'] ?? '') == 'yes' ? 1 : 0,
                    'is_crm_interview_attended' => $is_crm_interview_attended,
                    'is_in_crm_start_date' => ($row['is_in_crm_start_date'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_invoice' => ($row['is_in_crm_invoice'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_invoice_sent' => ($row['is_in_crm_invoice_sent'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_start_date_hold' => ($row['is_in_crm_start_date_hold'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_paid' => ($row['is_in_crm_paid'] ?? '') == 'yes' ? 1 : 0,
                    'is_in_crm_dispute' => ($row['is_in_crm_dispute'] ?? '') == 'yes' ? 1 : 0,
                    'is_job_within_radius' => $row['is_job_within_radius'] ?? 0,
                    'have_nursing_home_experience' => $row['have_nursing_home_experience'] ?? 0,
                    'paid_status' => $row['paid_status'] ?? null,
                    'paid_timestamp' => $paid_timestamp,
                    'status' => isset($row['status']) && strtolower($row['status']) == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];

                $processedData[] = ['row' => $rowIndex, 'data' => $processedRow, 'office' => $office];
            }

            // Batch insert/update
            $successfulRows = 0;
            foreach (array_chunk($processedData, 100) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $item) {
                            $rowIndex = $item['row'];
                            $row = $item['data'];
                            $office = $item['office'];
                            try {
                                $applicant = Applicant::updateOrCreate(
                                    ['id' => $row['id']],
                                    array_filter($row, fn($value) => !is_null($value))
                                );
                                if ($office) {
                                    $applicant->contacts()->updateOrCreate(
                                        [
                                            'contactable_id' => $office->id,
                                            'contactable_type' => Office::class,
                                        ],
                                        [
                                            'contact_name' => $row['applicant_name'],
                                            'contact_email' => $row['applicant_email'],
                                            'contact_phone' => $row['applicant_phone'],
                                            'contact_landline' => $row['applicant_landline'],
                                        ]
                                    );
                                    Log::channel('daily')->debug("Linked applicant ID {$applicant->id} to office ID {$office->id} for row {$rowIndex}");
                                }
                                Log::channel('daily')->info("Applicant created/updated: ID={$applicant->id}, Row={$rowIndex}");
                                $successfulRows++;
                            } catch (\Exception $e) {
                                Log::channel('daily')->error("Row {$rowIndex}: Failed to save applicant - {$e->getMessage()}");
                                $failedRows[] = ['row' => $rowIndex, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::channel('daily')->error("Failed to process chunk: {$e->getMessage()}");
                    $failedRows[] = ['row' => $index + 1, 'error' => "Chunk failed: {$e->getMessage()}"];
                }
            }

            // Clean up uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::channel('daily')->debug("Deleted temporary file: {$filePath}");
            }

            Log::channel('daily')->info("CSV/XLSX import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));
            return response()->json([
                'message' => 'File import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error("Import failed: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::channel('daily')->debug("Deleted temporary file after error: {$filePath}");
            }
            return response()->json([
                'error' => 'File import failed: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        } finally {
            // Ensure logs are flushed
            Log::close();
        }
    }
    public function getApplicantsAjaxRequest(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilters = $request->input('title_filters', ''); // Default is empty (no filter)
        $searchTerm = $request->input('search', ''); // This will get the search query

        $model = Applicant::query()
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name'
            ])
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->with(['jobTitle', 'jobCategory', 'jobSource']);

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
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

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
                });
            }
        }

        // Filter by status if it's not empty
        switch ($statusFilter) {
            case 'active':
                $model->where('applicants.status', 1)
                    ->where('applicants.is_no_job', false)
                    ->where('applicants.is_blocked', false)
                    ->where('applicants.is_temp_not_interested', false);
                break;
            case 'inactive':
                $model->where('applicants.status', 0)
                    ->where('applicants.is_no_job', false)
                    ->where('applicants.is_blocked', false)
                    ->where('applicants.is_temp_not_interested', false);
                break;
            case 'blocked':
                $model->where('applicants.is_blocked', true);
                break;
            case 'not interested':
                $model->where('applicants.is_temp_not_interested', true);
                break;
            case 'no job':
                $model->where('applicants.is_no_job', true);
                break;
        }

        // Filter by type if it's not empty
        switch ($typeFilter) {
            case 'specialist':
                $model->where('applicants.job_type', 'specialist');
                break;
            case 'regular':
                $model->where('applicants.job_type', 'regular');
                break;
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->where('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilters) {
            $model->whereIn('applicants.job_title_id', $titleFilters);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($applicant) {
                    $type = $applicant->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $applicant->jobCategory ? $applicant->jobCategory->name . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? $applicant->jobSource->name : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

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
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
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
                ->addColumn('applicant_email', function ($applicant) {
                    $email = '';
                    if($applicant->applicant_email_secondary){
                        $email = $applicant->applicant_email .'<br>'.$applicant->applicant_email_secondary; 
                    }else{
                        $email = $applicant->applicant_email;
                    }

                    return $email; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    if($applicant->lat != null && $applicant->lng != null){
                        $url = route('applicantsAvailableJobs', ['id' => $applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" target="_blank" style="color:blue;">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $notes = nl2br(htmlspecialchars($applicant->applicant_notes, ENT_QUOTES, 'UTF-8'));
                    return '
                        <a href="#" title="Add Short Note" style="color:blue" onclick="addShortNotesModal(\'' . (int)$applicant->id . '\')">
                            ' . $notes . '
                        </a>
                    ';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    $strng = '';
                    if($applicant->applicant_landline){
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $landline = '<strong>L:</strong> '.$applicant->applicant_landline;

                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone .'<br>'. $landline;
                    }else{
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone;
                    }

                    return $strng;
                })
                ->addColumn('created_at', function ($applicant) {
                    return $applicant->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($applicant) {
                    return $applicant->formatted_updated_at; // Using accessor
                })
                ->addColumn('applicant_resume', function ($applicant) {
                    if (!$applicant->is_blocked) {
                        $applicant_cv = (file_exists('public/storage/uploads/resume/' . $applicant->applicant_cv) || $applicant->applicant_cv != null)
                            ? '<a href="' . asset('storage/' . $applicant->applicant_cv) . '" title="Download CV" target="_blank">
                            <iconify-icon icon="solar:download-square-bold" class="text-success fs-28"></iconify-icon></a>'
                            : '<iconify-icon icon="solar:download-square-bold" class="text-light-grey fs-28"></iconify-icon>';
                    } else {
                        $applicant_cv = '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                    }

                    return $applicant_cv;
                })
                ->addColumn('crm_resume', function ($applicant) {
                    if (!$applicant->is_blocked) {
                        $updated_cv = (file_exists('public/storage/uploads/resume/' . $applicant->updated_cv) || $applicant->updated_cv != null)
                            ? '<a href="' . asset('storage/' . $applicant->updated_cv) . '" title="Download Updated CV" target="_blank">
                            <iconify-icon icon="solar:download-square-bold" class="text-primary fs-28"></iconify-icon></a>'
                            : '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                    } else {
                        $updated_cv = '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                    }

                    return $updated_cv;
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status = '';
                    if ($applicant->is_blocked == 1) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->is_no_response == 1) {
                        $status = '<span class="badge bg-warning">No Response</span>';
                    } elseif ($applicant->is_circuit_busy == 1) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job == 1) {
                        $status = '<span class="badge bg-warning">No Job</span>';
                    } elseif ($applicant->is_temp_not_interested == 1) {
                        $status = '<span class="badge bg-danger">Not<br>Interested</span>';
                    } elseif ($applicant->paid_status == 'open') {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_cv_in_quality_clear || 
                        $applicant->is_interview_confirm ||
                        $applicant->is_interview_attend ||
                        $applicant->is_in_crm_request ||
                        $applicant->is_crm_request_confirm ||
                        $applicant->is_crm_interview_attended != 0 ||
                        $applicant->is_in_crm_start_date ||
                        $applicant->is_in_crm_invoice ||
                        $applicant->is_in_crm_invoice_sent ||
                        $applicant->is_in_crm_start_date_hold ||
                        $applicant->is_in_crm_paid) {
                        $status = '<span class="badge bg-primary">CRM Active</span>';
                    } elseif ($applicant->status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->status == 0) {
                        $status = '<span class="badge bg-danger">Inactive</span>';
                    }

                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
                    $job_title = $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                    $job_category = $applicant->jobCategory ? ucwords($applicant->jobCategory->name) : '-';
                    $job_source = $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }
                    $html = '';
                    $html .= '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">';
                            if(Gate::allows('applicant-edit')){
                            $html .= '<li><a class="dropdown-item" href="' . route('applicants.edit', ['id' => (int)$applicant->id]) . '">Edit</a></li>';
                            }
                            if(Gate::allows('applicant-view')){
                                $html .= '<li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                        ' . (int)$applicant->id . ',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                        \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                        \'' . addslashes(htmlspecialchars($landline)) . '\',
                                        \'' . addslashes(htmlspecialchars($phone)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                        \'' . addslashes(htmlspecialchars($status)) . '\'
                                    )">View</a></li>';
                            }
                            if(Gate::allows('applicant-add-note')){
                                $html .= '<li><a class="dropdown-item" href="#" onclick="addNotesModal(' . (int)$applicant->id . ')">Add Note</a></li>';
                            }
                            if(Gate::allows('applicant-upload-resume')){
                                $html .= '<li>
                                        <a class="dropdown-item" href="#" onclick="triggerFileInput(' . (int)$applicant->id . ')">Upload Applicant Resume</a>
                                        <!-- Hidden File Input -->
                                        <input type="file" id="fileInput" style="display:none" accept=".pdf,.doc,.docx" onchange="uploadFile()">
                                    </li>';
                            }
                            if(Gate::allows('applicant-upload-crm-resume')){
                                $html .= '<li>
                                        <a class="dropdown-item" href="#" onclick="triggerCrmFileInput(' . (int)$applicant->id . ')">Upload CRM Resume</a>
                                        <!-- Hidden File Input -->
                                        <input type="file" id="crmfileInput" style="display:none" accept=".pdf,.doc,.docx" onchange="crmuploadFile()">
                                    </li>';
                            }
                            if (Gate::allows('applicant-view-history') || Gate::allows('applicant-view-notes-history')) {
                                $html .= '<li><hr class="dropdown-divider"></li>';
                            }

                            $html .= '<!-- <li><a class="dropdown-item" target="_blank" href="' . route('applicants.available_no_job', ['id' => (int)$applicant->id, 'radius' => 15]) . '">Go to No Job</a></li> -->';
                            if(Gate::allows('applicant-view-history')){
                                $html .= '<li><a class="dropdown-item" target="_blank" href="' . route('applicants.history', ['id' => (int)$applicant->id]) . '">View History</a></li>';
                            }
                            if(Gate::allows('applicant-view-notes-history')){
                                $html .= '<li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . (int)$applicant->id . ')">Notes History</a></li>';
                            }
                            $html .= '</ul>
                        </div>';

                        return $html;
                })
                ->rawColumns(['applicant_notes', 'applicant_phone', 'applicant_postcode', 'job_title', 'applicant_experience', 'applicant_email', 'applicant_resume', 'crm_resume', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function getJobTitlesByCategory(Request $request)
    {
        $jobTitles = JobTitle::where('job_category_id', $request->input('job_category_id'))
            ->where('type', $request->input('job_type'))->get();

        return response()->json($jobTitles);
    }
    public function storeShortNotes(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|integer|exists:applicants,id',
            'details' => 'required|string',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $applicant_id = $request->input('applicant_id');
            $details = $request->input('details');
            $notes_reason = $request->input('reason');
            $applicant_notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

            $updateData = ['applicant_notes' => $applicant_notes];
            $movedTabTo = '';

            switch ($notes_reason) {
                case 'blocked':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_no_response' => false,
                        'is_blocked' => true,
                        'is_callback_enable' => false,
                        'is_temp_not_interested' => false,
                    ]));
                    $movedTabTo = 'blocked';
                    break;

                case 'casual':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_no_response' => false,
                        'is_blocked' => false,
                        'is_callback_enable' => false,
                        'is_temp_not_interested' => false,
                    ]));
                    $movedTabTo = 'casual';
                    break;

                case 'no_response':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_circuit_busy' => false,
                        'is_no_response' => true,
                        'is_callback_enable' => false,
                        'is_blocked' => false,
                        'is_temp_not_interested' => false,
                    ]));
                    $movedTabTo = 'no response';
                    break;

                case 'no_job':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_no_response' => false,
                        'is_callback_enable' => false,
                        'is_blocked' => false,
                        'is_no_job' => true,
                        'is_temp_not_interested' => false,
                    ]));
                    $movedTabTo = 'no job';
                    break;

                case 'circuit_busy':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_temp_not_interested' => false,
                        'is_callback_enable' => false,
                        'is_no_response' => false,
                        'is_circuit_busy' => true,
                        'is_blocked' => false,
                        'is_no_job' => false,
                    ]));
                    $movedTabTo = 'circuit busy';
                    break;

                case 'not_interested':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_temp_not_interested' => true,
                        'is_no_response' => false,
                        'is_callback_enable' => false,
                        'is_circuit_busy' => false,
                        'is_blocked' => false,
                        'is_no_job' => false,
                    ]));
                    $movedTabTo = 'not interested';
                    break;

                case 'callback':
                    Applicant::where('id', $applicant_id)->update(array_merge($updateData, [
                        'is_temp_not_interested' => false,
                        'is_callback_enable' => true,
                        'is_no_response' => false,
                        'is_circuit_busy' => false,
                        'is_blocked' => false,
                        'is_no_job' => false,
                    ]));
                    $movedTabTo = 'callback';
                    break;
            }

            // Save applicant note
            $applicantNote = ApplicantNote::create([
                'details' => $applicant_notes,
                'applicant_id' => $applicant_id,
                'moved_tab_to' => $movedTabTo,
                'user_id' => $user->id,
            ]);

            $applicantNote->update([
                'note_uid' => md5($applicantNote->id),
            ]);

            // Disable previous module notes
            ModuleNote::where([
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant',
            ])
            ->where('status', 1)
            ->update(['status' => 0]);

            // Add new module note
            $moduleNote = ModuleNote::create([
                'details' => $applicant_notes,
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant',
                'user_id' => $user->id,
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id),
            ]);

            // Log audit
            $applicant = Applicant::find($applicant_id)->select('applicant_name', 'id')->first();
            $observer = new ActionObserver();
            $observer->customApplicantAudit($applicant, 'applicant_notes');

            DB::commit();

            return redirect()->to(url()->previous());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store notes: ' . $e->getMessage());

            return back()->with('error', 'Something went wrong while saving notes.');
        }
    }
    public function downloadCv($id)
    {
        $applicant = Applicant::findOrFail($id);
        $filePath = $applicant->cv_path;

        if (Storage::exists($filePath)) {
            return Storage::download($filePath);
        } else {
            return response()->json(['error' => 'File not found'], 404);
        }
    }
    public function edit($id)
    {
        // Debug the incoming id
        Log::info('Trying to edit applicant with ID: ' . $id);

        $applicant = Applicant::find($id);

        // Check if the applicant is found
        if (!$applicant) {
            Log::info('Applicant not found with ID: ' . $id);
        }

        return view('applicants.edit', compact('applicant'));
    }
    public function update(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'job_category_id' => 'required|exists:job_categories,id',
            'job_type' => ['required', Rule::in(['specialist', 'regular'])],
            'job_title_id' => 'required|exists:job_titles,id',
            'job_source_id' => 'required|exists:job_sources,id',
            'applicant_name' => 'required|string|max:255',
            'gender' => 'required',
            'applicant_email' => 'required|email|max:255|unique:applicants,applicant_email,' . $request->input('applicant_id'), // Exclude current applicant's email
            'applicant_email_secondary' => 'nullable|email|max:255|unique:applicants,applicant_email_secondary,' . $request->input('applicant_id'),
            'applicant_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'applicant_phone' => 'required|string|max:20|unique:applicants,applicant_phone,' . $request->input('applicant_id'),
            'applicant_landline' => 'nullable|string|max:20|unique:applicants,applicant_landline,' . $request->input('applicant_id'),
            'applicant_experience' => 'nullable|string',
            'applicant_notes' => 'required|string|max:255',
            'applicant_cv' => 'nullable|mimes:docx,doc,csv,pdf|max:5000',
        ]);

        // Add conditionally required validation
        $validator->sometimes('have_nursing_home_experience', 'required|boolean', function ($input) {
            $nurseCategory = JobCategory::where('name', 'nurse')->first();
            return $nurseCategory && $input->job_category_id == $nurseCategory->id;
        });

        // If validation fails, return with errors
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Please fix the errors in the form'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Prepare the data to update
            $applicantData = $request->only([
                'job_category_id',
                'job_type',
                'job_title_id',
                'job_source_id',
                'applicant_name',
                'applicant_email',
                'applicant_email_secondary',
                'applicant_postcode',
                'applicant_phone',
                'applicant_landline',
                'applicant_experience',
                'applicant_notes',
                'have_nursing_home_experience',
                'gender'
            ]);

            // Handle file upload if a CV is provided
            $path = null;
            if ($request->hasFile('applicant_cv')) {
                // Get the original file name
                $filenameWithExt = $request->file('applicant_cv')->getClientOriginalName();
                // Get the filename without extension
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                // Get file extension
                $extension = $request->file('applicant_cv')->getClientOriginalExtension();
                // Create a new filename with a timestamp
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                // Store the file in the public/uploads/resume/ directory
                $path = $request->file('applicant_cv')->storeAs('uploads/resume', $fileNameToStore, 'public');

                // If a CV was uploaded, assign the path to the data
                $applicantData['applicant_cv'] = $path;
            }

            // Get the applicant ID from the request
            $id = $request->input('applicant_id');

            // Retrieve the applicant record
            $applicant = Applicant::find($id);

            // If the applicant doesn't exist, throw an exception
            if (!$applicant) {
                throw new Exception("Applicant not found with ID: " . $id);
            }

            $applicantData['applicant_notes'] = $applicant_notes = $request->applicant_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            $postcode = $request->applicant_postcode;

            if ($postcode != $applicant->applicant_postcode) {
                $postcode_query = strlen($postcode) < 6
                    ? DB::table('outcodepostcodes')->where('outcode', $postcode)->first()
                    : DB::table('postcodes')->where('postcode', $postcode)->first();

                if (!$postcode_query) {
                    try {
                        $result = $this->geocode($postcode);

                        // If geocode fails, throw
                        if (!isset($result['lat']) || !isset($result['lng'])) {
                            throw new Exception('Geolocation failed. Latitude and longitude not found.');
                        }

                        $applicantData['lat'] = $result['lat'];
                        $applicantData['lng'] = $result['lng'];
                    } catch (Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unable to locate address: ' . $e->getMessage()
                        ], 400);
                    }
                } else {
                    $applicantData['lat'] = $postcode_query->lat;
                    $applicantData['lng'] = $postcode_query->lng;
                }
            }

            // Update the applicant with the validated and formatted data
            $applicant->update($applicantData);

            ModuleNote::where([
                'module_noteable_id' => $id,
                'module_noteable_type' => 'Horsefly\Applicant'
            ])
                ->where('status', 1)
                ->update(['status' => 0]);

            $moduleNote = ModuleNote::create([
                'details' => $applicant_notes,
                'module_noteable_id' => $applicant->id,
                'module_noteable_type' => 'Horsefly\Applicant',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            DB::commit();

            // Redirect to the applicants page with a success message
            return response()->json([
                'success' => true,
                'message' => 'Applicant updated successfully',
                'redirect' => route('applicants.list')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the applicant. Please try again.'
            ], 500);
        }
    }
    public function destroy($id)
    {
        $applicant = Applicant::findOrFail($id);
        $applicant->delete();
        return redirect()->route('applicants.list')->with('success', 'Applicant deleted successfully');
    }
    public function show($id)
    {
        $applicant = Applicant::findOrFail($id);
        return view('applicants.show', compact('applicant'));
    }
    public function uploadCv(Request $request)
    {
        // Validate the request (check if a file was uploaded)
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240', // Validating file type and size
            'applicant_id' => 'required|integer|exists:applicants,id', // Validate applicant ID
        ]);

        // Get the file from the request
        $file = $request->file('resume');

        // Get the applicant ID from the request
        $applicantId = $request->input('applicant_id');

        // Define the file path where you want to store the resume
        // You can optionally use a unique name for the file, or keep the original name
        $fileName = time() . $applicantId . '.' . $file->getClientOriginalExtension();

        // Store the file in public/storage/uploads directory
        // The 'public' disk will store the file in the 'public/storage' directory
        $filePath = $file->storeAs('uploads/resume/', $fileName, 'public');

        // Retrieve the applicant
        $applicant = Applicant::findOrFail($applicantId);

        // If applicant_cv is null, save the file path in 'applicant_cv'
        $applicant->update(['applicant_cv' => $filePath]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_path' => $filePath, // You can return the path or save it in the database if needed
        ]);
    }
    public function crmuploadCv(Request $request)
    {
        // Validate the request (check if a file was uploaded)
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240', // Validating file type and size
            'applicant_id' => 'required|integer|exists:applicants,id', // Validate applicant ID
        ]);

        // Get the file from the request
        $file = $request->file('resume');

        // Get the applicant ID from the request
        $applicantId = $request->input('applicant_id');

        // Define the file path where you want to store the resume
        // You can optionally use a unique name for the file, or keep the original name
        $fileName = time() . $applicantId . '.' . $file->getClientOriginalExtension();

        // Store the file in public/storage/uploads directory
        // The 'public' disk will store the file in the 'public/storage' directory
        $filePath = $file->storeAs('uploads/resume/', $fileName, 'public');

        // Retrieve the applicant
        $applicant = Applicant::findOrFail($applicantId);

        // If applicant_cv already exists, save the file path in 'updated_cv'
        $applicant->update(['updated_cv' => $filePath]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_path' => $filePath, // You can return the path or save it in the database if needed
        ]);
    }
    public function export(Request $request)
    {
        $type = $request->query('type', 'all'); // Default to 'all' if not provided
        $radius = $request->query('radius', null); // Default to 0 if not provided
        $model_type = $request->query('model_type', null);
        $model_id = $request->query('model_id', null);

        if ($radius != null) {
            $sale = Sale::find($model_id);
            $fileName = "applicants_within_{$radius}km_of_sale_{$sale->sale_postcode}.csv";
        } else {
            $fileName = "applicants_{$type}.csv";
        }

        return Excel::download(new ApplicantsExport($type, $radius, $model_type, $model_id), $fileName);
    }
    public function changeStatus(Request $request)
    {
        $user = Auth::user();

        $applicant_id = $request->input('applicant_id');
        $status = $request->input('status');
        $details = $request->input('details');
        $notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

        $updateData = [
            'applicant_notes' => $notes,
            'status' => $status,
        ];

        Applicant::where('id', $applicant_id)->update($updateData);

        // Disable previous module note
        ModuleNote::where([
            'module_noteable_id' => $applicant_id,
            'module_noteable_type' => 'Horsefly\Applicant'
        ])
            ->orderBy('id', 'desc')
            ->update(['status' => 0]);

        // Create new module note
       $moduleNote = ModuleNote::create([
            'details' => $notes,
            'module_noteable_id' => $applicant_id,
            'module_noteable_type' => 'Horsefly\Applicant',
            'user_id' => $user->id
        ]);

        $moduleNote->update([
            'module_note_uid' => md5($moduleNote->id)
        ]);

        return redirect()->to(url()->previous());
    }
    public function getApplicantHistoryAjaxRequest(Request $request)
    {
        $id = $request->applicant_id;
        // Prepare CRM Notes query
        $model = Applicant::query()
            ->with('callback_notes', 'no_nursing_home_notes')
            ->join(DB::raw('
                (
                    SELECT *
                    FROM crm_notes
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM crm_notes
                        GROUP BY applicant_id, sale_id
                    )
                ) AS crm_notes
            '), 'crm_notes.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'crm_notes.sale_id')
            ->join('offices', 'offices.id', '=', 'sales.office_id')
            ->join('units', 'units.id', '=', 'sales.unit_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
                    ->on('crm_notes.sale_id', '=', 'history.sale_id');
            })
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->select([
                "applicants.id as app_id",
                "applicants.applicant_name",
                "crm_notes.id as crm_notes_id",
                "crm_notes.details as note_details",
                "crm_notes.created_at as notes_created_at",
                "sales.id as sale_id",
                "sales.sale_postcode",
                "sales.is_on_hold",
                "sales.status as sale_status",
                "sales.job_type as sale_job_type",
                "sales.position_type",
                "sales.experience as sale_experience",
                "sales.qualification as sale_qualification",
                "sales.salary",
                "sales.timing",
                "sales.created_at as sale_posted_date",
                "sales.benefits",
                "history.sub_stage",
                "history.created_at",
                "offices.office_name",
                "units.unit_name",
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
            ])
            ->where([
                'applicants.id' => $id,
                'history.status' => 1
            ]);

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_category') {
                $model->orderBy('job_category_name', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('job_title_name', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            } else {
                $model->orderBy('history.created_at', 'desc');
            }
        } else {
            $model->orderBy('history.created_at', 'desc');
        }

        // Apply search filter BEFORE sending to DataTables
        if ($request->has('search.value')) {
            $searchTerm = $request->input('search.value');
            $model->where(function ($query) use ($searchTerm) {
                $query->where('history.sub_stage', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('history.created_at', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('crm_notes.details', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('sales.sale_postcode', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('job_titles.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('job_categories.name', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Handle AJAX request
        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y, h:i A');
                })
                ->addColumn('job_title', function ($row) {
                    return $row->job_title_name ? strtoupper($row->job_title_name) : '-';
                })
                ->addColumn('sub_stage', function ($row) {
                    return '<span class="badge bg-primary">' . ucwords(str_replace('_', ' ', $row->sub_stage)) . '</span>';
                })
                ->addColumn('details', function ($row) {
                    $short = Str::limit(strip_tags($row->note_details), 100);
                    $full = e($row->note_details);
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
                                        <h5 class="modal-title" id="' . $id . '-label">Notes</h5>
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
                    // Tooltip content with additional data-bs-placement and title
                    return $notes;
                })
                ->addColumn('job_details', function ($row) {
                    $position_type = strtoupper(str_replace('-', ' ', $row->position_type));
                    $position = '<span class="badge bg-primary">' . htmlspecialchars($position_type, ENT_QUOTES) . '</span>';

                    if ($row->sale_status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($row->sale_status == 0 && $row->is_on_hold == 0) {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($row->sale_status == 2) {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($row->sale_status == 3) {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    // Escape HTML in $status for JavaScript (to prevent XSS)
                    $escapedStatus = htmlspecialchars($status, ENT_QUOTES);

                    // Prepare modal HTML for the "Job Details"
                    $modalHtml = $this->generateJobDetailsModal($row);

                    // Return the action link with a modal trigger and the modal HTML
                    return '<a href="#" class="dropdown-item" style="color: blue;" onclick="showDetailsModal('
                        . (int)$row->sale_id . ','
                        . '\'' . htmlspecialchars(Carbon::parse($row->sale_posted_date)->format('d M Y, h:i A'), ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->office_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->unit_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->sale_postcode, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->job_category_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->job_title_name, ENT_QUOTES) . '\','
                        . '\'' . $escapedStatus . '\','
                        . '\'' . htmlspecialchars($row->timing, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->sale_experience, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->salary, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($position, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->sale_qualification, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars($row->benefits, ENT_QUOTES) . '\')">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                        </a>' . $modalHtml;
                })
                ->addColumn('job_category', function ($row) {
                    $type = $row->sale_job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $row->job_category_name ? $row->job_category_name . $stype : '-';
                })
                ->addColumn('action', function ($row) {
                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View All Notes" onclick="viewNotesHistory(\'' . (int)$row->app_id . '\',\'' . (int)$row->sale_id . '\')">
                                <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                            </a>';
                })
                ->rawColumns(['details', 'job_category', 'job_title', 'job_details', 'action', 'sub_stage'])
                ->make(true);
        }
    }
    private function generateJobDetailsModal($data)
    {
        $modalId = 'jobDetailsModal_' . $data->sale_id;  // Unique modal ID for each applicant's job details

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
    public function sendCVtoQuality(Request $request)
    {
        try {
            $input = $request->all();
            $request->replace($input);

            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'applicant_id' => "required|integer|exists:applicants,id",
                'sale_id' => "required|integer|exists:sales,id",
                'details' => "required|string",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Please fix the errors in the form'
                ], 422);
            }

            DB::beginTransaction();

            $details = $request->input('details');

            $applicant = Applicant::findOrFail($request->input('applicant_id'));
            $sale = Sale::findOrFail($request->input('sale_id'));

            // Check if job titles match
            if ($applicant->job_title_id != $sale->job_title_id) {
                throw new Exception("CV can't be sent - job titles don't match");
            }

            $noteDetail = '';
            if ($request->has('hangup_call') && $request->input('hangup_call') == 'on') {
                $noteDetail .= $this->handleHangupCall($request, $user, $applicant, $sale, $details);
            } elseif ($request->has('no_job') && $request->input('no_job') == 'on') {
                $noteDetail .= $this->handleNoJob($request, $user, $applicant);
            } else {
                $noteDetail .= $this->handleRegularSubmission($request, $user);
            }

            $noteDetail .= $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

            // Check CV limits
            $sent_cv_count = CVNote::where([
                'sale_id' => $sale->id,
                'status' => 1
            ])->count();

            $open_cv_count = History::where([
                'sale_id' => $sale->id,
                'status' => 1,
                'sub_stage' => 'quality_cvs_hold'
            ])->count();

            $net_sent_cv_count = $sent_cv_count - $open_cv_count;

            if ($net_sent_cv_count >= $sale->cv_limit) {
                throw new Exception("Sorry, you can't send more CVs for this job. The maximum CV limit has been reached.");
            }

            // Check if applicant is rejected
            $isRejected = $this->checkIfApplicantRejected($applicant);
            if ($isRejected) {
                throw new Exception("This applicant CV can't be sent.");
            }

            // Update applicant and create records
            $applicant->update(['is_cv_in_quality' => true]);

            $cv_note = CVNote::create([
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'applicant_id' => $applicant->id,
                'details' => $noteDetail,
            ]);
            $cv_note->update(['cv_uid' => md5($cv_note->id)]);

            History::where('applicant_id', $applicant->id)->update(['status' => 0]);

            $history = History::create([
                'sale_id' => $sale->id,
                'applicant_id' => $applicant->id,
                'user_id' => $user->id,
                'stage' => 'quality',
                'sub_stage' => 'quality_cvs',
            ]);
            $history->update(['history_uid' => md5($history->id)]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CV successfully sent to quality'
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Record not found: ' . $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function importApplicantsFromCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            return back()->with('error', 'Unable to open file.');
        }

        $header = fgetcsv($handle); // Get header row
        $inserted = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            // Skip if mandatory fields missing
            if (empty($data['applicant_email']) || empty($data['applicant_name'])) {
                $skipped++;
                continue;
            }

            // Pattern to detect email
            $emailPattern = '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i';

            // Check in applicant_name
            if (preg_match($emailPattern, $data['applicant_name'], $matches)) {
                $data['applicant_email_secondary'] = $matches[0];
                $data['applicant_name'] = preg_replace($emailPattern, '', $data['applicant_name']);
            }

            // Check in applicant_postcode
            if (preg_match($emailPattern, $data['applicant_postcode'], $matches)) {
                $data['applicant_email_secondary'] = $matches[0];
                $data['applicant_postcode'] = preg_replace($emailPattern, '', $data['applicant_postcode']);
            }

            if (!empty($data['job_title_id'])) {
                if ($data['job_title_id'] == 'nonnurse specialist') {
                    $jobType = 'specialist';
                } else {
                    $jobType = 'regular';
                }

                $data['job_type'] = $jobType;
            }

            if (!empty($data['job_category'])) {
                $jobCategorySlug = str_replace('-', ' ', $data['job_category']);

                // Lookup job category ID by slug (assuming you store slugs in DB)
                $category = JobCategory::where('name', $jobCategorySlug)->first();

                if ($category) {
                    $data['job_category_id'] = $category->id;
                }
            }

            if (!empty($data['applicant_source'])) {
                // Lookup job category ID by slug (assuming you store slugs in DB)
                $source = JobSource::where('name', $data['applicant_source'])->first();

                if ($source) {
                    $data['job_source_id'] = $source->id;
                }
            }

            if (!empty($data['job_title_prof'])) {
                $specialistJobTitles = [
                    ['id' => 1, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Navigator'],
                    ['id' => 2, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Legal Executive'],
                    ['id' => 3, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Lawyer'],
                    ['id' => 4, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Conveyancer'],
                    ['id' => 5, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Solicitor'],
                    ['id' => 6, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Practice Manager'],
                    ['id' => 7, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Paralegals'],
                    ['id' => 8, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Dentist'],
                    ['id' => 9, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Associate Dentist'],
                    ['id' => 10, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Orthodontics'],
                    ['id' => 11, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Dental Receptionist'],
                    ['id' => 12, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Fee Earner'],
                    ['id' => 13, 'specialist_title' => 'nonnurse specialist', 'specialist_prof' => 'Secretary'],
                ];

                foreach ($specialistJobTitles as $job) {
                    JobTitle::create($job);
                }
                // Lookup job category ID by slug (assuming you store slugs in DB)
                $title = JobTitle::where('name', $data['job_title_prof'])->first();

                if ($title) {
                    $data['job_title_prof'] = $title->id;
                }
            }

            // Transformations
            $data['status'] = strtolower($data['status']) === 'active' ? 1 : 0;
            $data['is_blocked'] = (int) $data['is_blocked'] ?? 0;
            $data['applicant_postcode'] = preg_replace('/[^A-Za-z0-9 ]/', '', $data['applicant_postcode']);
            $data['applicant_email'] = preg_replace('/\s+/', '', $data['applicant_email']);

            try {
                Applicant::updateOrCreate(
                    ['applicant_uid' => $data['applicant_u_id']],
                    [
                        'user_id '     => $data['applicant_user_id'],
                        'applicant_name'     => $data['applicant_name'],
                        'applicant_email' => $data['applicant_email'],
                        'applicant_email_secondary' => $data['applicant_email_secondary'],
                        'applicant_postcode' => $data['applicant_postcode'],
                        'applicant_phone'    => $data['applicant_phone'],
                        'applicant_landline'    => $data['applicant_homePhone'],
                        'job_category_id'    => $data['job_category_id'],
                        'job_title_id'    => $data['job_title_id'],
                        'job_source_id'    => $data['job_source_id'],
                        'job_type'           => $data['job_type'],
                        'lat'                => $data['lat'] ?? null,
                        'lng'                => $data['lng'] ?? null,
                        'status'             => $data['status'],
                        'is_blocked'         => $data['is_blocked'] ?? 0,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]
                );
                $inserted++;
            } catch (\Exception $e) {
                Log::error('Import failed: ' . $e->getMessage());
                $skipped++;
            }
        }

        fclose($handle);

        return back()->with('success', "$inserted applicants imported. $skipped skipped.");
    }

    // Helper methods
    private function handleHangupCall($request, $user, $applicant, $sale, $notes)
    {
        $noteDetail = '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
        $noteDetail .= '<strong>Call Hung up/Not Interested:</strong> Yes<br>';
        $noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
        $noteDetail .= '<strong>By:</strong> ' . $user->name . '<br>';

        $applicant->update([
            'is_temp_not_interested' => true,
            'is_no_job' => false
        ]);

        $pivotSale = ApplicantPivotSale::create([
            'applicant_id' => $applicant->id,
            'sale_id' => $sale->id,
            'pivot_uid' => null
        ]);
        $pivotSale->update(['pivot_uid' => md5($pivotSale->id)]);

        $notes_for_range = NotesForRangeApplicant::create([
            'applicants_pivot_sales_id' => $pivotSale->id,
            'reason' => $notes,
            'range_uid' => null
        ]);
        $notes_for_range->update(['range_uid' => $notes_for_range->id]);

        return $noteDetail;
    }
    private function handleNoJob($request, $user, $applicant)
    {
        $noteDetail = '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
        $noteDetail .= '<strong>No Job:</strong> Yes<br>';
        $noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
        $noteDetail .= '<strong>By:</strong> ' . $user->name . '<br>';

        $applicant->update([
            'is_no_response' => false,
            'is_temp_not_interested' => false,
            'is_blocked' => false,
            'is_circuit_busy' => false,
            'is_no_job' => true,
            'applicant_notes' => $noteDetail,
            'updated_at' => Carbon::now()
        ]);

        return $noteDetail;
    }
    private function handleRegularSubmission($request, $user)
    {
        $transportType = $request->has('transport_type') ? implode(', ', $request->input('transport_type')) : '';
        $shiftPattern = $request->has('shift_pattern') ? implode(', ', $request->input('shift_pattern')) : '';

        $noteDetail = '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
        $noteDetail .= '<strong>Current Employer Name:</strong> ' . htmlspecialchars($request->input('current_employer_name')) . '<br>';
        $noteDetail .= '<strong>PostCode:</strong> ' . htmlspecialchars($request->input('postcode')) . '<br>';
        $noteDetail .= '<strong>Current/Expected Salary:</strong> ' . htmlspecialchars($request->input('expected_salary')) . '<br>';
        $noteDetail .= '<strong>Qualification:</strong> ' . htmlspecialchars($request->input('qualification')) . '<br>';
        $noteDetail .= '<strong>Transport Type:</strong> ' . htmlspecialchars($transportType) . '<br>';
        $noteDetail .= '<strong>Shift Pattern:</strong> ' . htmlspecialchars($shiftPattern) . '<br>';
        $noteDetail .= '<strong>Nursing Home:</strong> ' . ($request->has('nursing_home') && $request->input('nursing_home') == 'on' ? 'Yes' : 'No') . '<br>';
        $noteDetail .= '<strong>Alternate Weekend:</strong> ' . ($request->has('alternate_weekend') && $request->input('alternate_weekend') == 'on' ? 'Yes' : 'No') . '<br>';
        $noteDetail .= '<strong>Interview Availability:</strong> ' . ($request->has('interview_availability') && $request->input('interview_availability') == 'on' ? 'Available' : 'Not Available') . '<br>';
        $noteDetail .= '<strong>No Job:</strong> ' . ($request->input('no_job') && $request->input('no_job') == 'on' ? 'Yes' : 'No') . '<br>';
        $noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
        $noteDetail .= '<strong>By:</strong> ' . $user->name . '<br>';

        return $noteDetail;
    }
    private function checkIfApplicantRejected($applicant)
    {
        return Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->where(function ($query) {
                $query->where('applicants.is_in_crm_reject', true)
                    ->orWhere('applicants.is_in_crm_request_reject', true)
                    ->orWhere('applicants.is_crm_interview_attended', false)
                    ->orWhere('applicants.is_in_crm_start_date_hold', true)
                    ->orWhere('applicants.is_in_crm_dispute', true)
                    ->orWhere(function ($q) {
                        $q->where('applicants.is_cv_reject', true)
                            ->where('quality_notes.moved_tab_to', 'rejected');
                    });
            })
            ->where('applicants.status', 1)
            ->where('applicants.id', $applicant->id)
            ->exists();
    }
    public function markApplicantNoNursingHome(Request $request)
    {
        $user = Auth::user();

        try {
            $applicant_id = $request->input('applicant_id');
            $sale_id = $request->input('sale_id');
            $details = $request->input('details');
            $notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

            // Deactivate previous similar notes
            ApplicantNote::where('applicant_id', $applicant_id)
                ->whereIn('moved_tab_to', ['no_nursing_home', 'revert_no_nursing_home'])
                ->where('status', 1)
                ->update(['status' => 0]);

            // Create new note
            $applicant_note = ApplicantNote::create([
                'user_id' => $user->id,
                'applicant_id' => $applicant_id,
                'details' => $notes,
                'moved_tab_to' => 'no_nursing_home'
            ]);

            $applicant_note->update([
                'note_uid' => md5($applicant_note->id)
            ]);

            // Update applicant status
            $applicant = Applicant::where('id', $applicant_id)->first();

            $applicant->update(['is_in_nurse_home' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Marked as no nursing home experience successfully!',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error marking applicant as no nursing home: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! Please try again.',
            ], 500);
        }
    }
    public function availableJobsIndex($applicant_id, $radius = null)
    {
        $applicant = Applicant::find($applicant_id);
        $jobCategory = JobCategory::where('id', $applicant->job_category_id)->select('name')->first();
        $jobTitle = JobTitle::where('id', $applicant->job_title_id)->select('name')->first();
        $jobSource = JobSource::where('id', $applicant->job_source_id)->select('name')->first();
        $jobType = ucwords(str_replace('-', ' ', $applicant->job_type));
        $jobType = $jobType == 'Specialist' ? ' (' . $jobType . ')' : '';

        // Convert radius to miles if provided in kilometers (1 km ≈ 0.621371 miles)
        $radiusInMiles = round($radius * 0.621371, 1);

        return view('applicants.available-jobs', compact('applicant', 'jobCategory', 'jobTitle', 'jobSource', 'radius', 'radiusInMiles', 'jobType'));
    }
    public function availableNoJobsIndex($applicant_id, $radius = null)
    {
        $applicant = Applicant::find($applicant_id);
        $jobCategory = JobCategory::where('id', $applicant->job_category_id)->select('name')->first();
        $jobTitle = JobTitle::where('id', $applicant->job_title_id)->select('name')->first();
        $jobSource = JobSource::where('id', $applicant->job_source_id)->select('name')->first();
        $jobType = ucwords(str_replace('-', ' ', $applicant->job_type));
        $jobType = $jobType == 'Specialist' ? ' (' . $jobType . ')' : '';

        // Convert radius to miles if provided in kilometers (1 km ≈ 0.621371 miles)
        $radiusInMiles = round($radius * 0.621371, 1);

        return view('applicants.available-no-jobs', compact('applicant', 'jobCategory', 'jobTitle', 'jobSource', 'radius', 'radiusInMiles', 'jobType'));
    }
    public function getAvailableJobs(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $limitCountFilter = $request->input('cv_limit_filter', ''); // Default is empty (no filter)
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)
        $applicant_id = $request->input('applicant_id'); // Default is empty (no filter)
        $radius = $request->input('radius'); // Default is empty (no filter)

        $applicant = Applicant::with('cv_notes')->find($applicant_id);
        $lat = $applicant->lat;
        $lon = $applicant->lng;

        $model = Sale::query()
            ->select([
                'sales.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'units.unit_name as unit_name',
                'users.name as user_name',
                DB::raw("((ACOS(SIN($lat * PI() / 180) * SIN(sales.lat * PI() / 180) + 
                        COS($lat * PI() / 180) * COS(sales.lat * PI() / 180) * COS(($lon - sales.lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
                        AS distance")
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->whereNotExists(function ($query) use ($applicant_id) {
                $query->select(DB::raw(1))
                    ->from('applicants_pivot_sales')
                    ->whereColumn('applicants_pivot_sales.sale_id', 'sales.id')
                    ->where('applicants_pivot_sales.applicant_id', $applicant_id);
            })
            ->where('sales.status', 1) // Only active sales
            ->having("distance", "<", $radius)
            ->orderBy("distance")
            ->with(['jobTitle', 'jobCategory', 'unit', 'office', 'user'])
            ->selectRaw(DB::raw("(SELECT COUNT(*) FROM cv_notes WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1) as no_of_sent_cv"));


        $jobTitle = JobTitle::find($applicant->job_title_id);

        // Decode related_titles safely and normalize
        $relatedTitles = is_array($jobTitle->related_titles)
            ? $jobTitle->related_titles
            : json_decode($jobTitle->related_titles ?? '[]', true);

        // Make sure it's an array, lowercase all, and add main title
        $titles = collect($relatedTitles)
            ->map(fn($item) => strtolower(trim($item)))
            ->push(strtolower(trim($jobTitle->name)))
            ->unique()
            ->values()
            ->toArray();


        $jobTitleIds = JobTitle::whereIn(DB::raw('LOWER(name)'), $titles)->pluck('id')->toArray();

        $model->whereIn('sales.job_title_id', $jobTitleIds);

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    $likeSearch = "%{$searchTerm}%";

                    $query->whereRaw('LOWER(sales.sale_postcode) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.experience) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.timing) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_description) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.position_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.cv_limit) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.salary) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.benefits) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.qualification) LIKE ?', [$likeSearch]);

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($likeSearch) {
                        $q->where('job_titles.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($likeSearch) {
                        $q->where('job_categories.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('unit', function ($q) use ($likeSearch) {
                        $q->where('units.unit_name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('office', function ($q) use ($likeSearch) {
                        $q->where('offices.office_name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('user', function ($q) use ($likeSearch) {
                        $q->where('users.name', 'LIKE', "%{$likeSearch}%");
                    });
                });
            }
        }

        // Filter by status if it's not empty
        switch ($statusFilter) {
            case 'active':
                $model->where('sales.status', 1);
                break;
            case 'closed':
                $model->where('sales.status', 0)
                    ->where('sales.is_on_hold', 0);
                break;
            case 'pending':
                $model->where('sales.status', 2);
                break;
            case 'rejected':
                $model->where('sales.status', 3);
                break;
            case 'on hold':
                $model->where('sales.is_on_hold', true);
                break;
        }

        // Filter by type if it's not empty
        switch ($typeFilter) {
            case 'specialist':
                $model->where('sales.job_type', 'specialist');
                break;
            case 'regular':
                $model->where('sales.job_type', 'regular');
                break;
        }

        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->where('sales.office_id', $officeFilter);
        }

        // Filter by category if it's not empty
        if ($limitCountFilter) {
            $model->where('sales.cv_limit', $limitCountFilter);
        }

        // Filter by category if it's not empty
        if ($categoryFilter) {
            $model->where('sales.job_category_id', $categoryFilter);
        }

        // Filter by category if it's not empty
        if ($titleFilter) {
            $model->where('sales.job_title_id', $titleFilter);
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('sales.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('sales.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('sales.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('sales.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('sales.updated_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('office_name', function ($sale) {
                    $office_id = $sale->office_id;
                    $office = Office::find($office_id);
                    return $office ? $office->office_name : '-';
                })
                ->addColumn('unit_name', function ($sale) {
                    $unit_id = $sale->unit_id;
                    $unit = Unit::find($unit_id);
                    return $unit ? $unit->unit_name : '-';
                })
                ->addColumn('cv_limit', function ($sale) {
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >' . $sale->no_of_sent_cv . '/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int)$sale->cv_limit - (int)$sale->no_of_sent_cv . '/' . (int)$sale->cv_limit) . "<br>Limit Remains</span>";
                    return $status;
                })
                ->addColumn('job_title', function ($sale) {
                    return $sale->jobTitle ? $sale->jobTitle->name : '-';
                })
                ->addColumn('open_date', function ($sale) {
                    return $sale->open_date ? Carbon::parse($sale->open_date)->format('d M Y, h:i A') : '-'; // Using accessor
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? $sale->jobCategory->name . $stype : '-';
                })
                ->addColumn('experience', function ($sale) {
                    $short = Str::limit(strip_tags($sale->experience), 80);
                    $full = e($sale->experience);
                    $id = 'exp-' . $sale->id;

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
                                        <h5 class="modal-title" id="' . $id . '-label">Sale Experience</h5>
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
                ->addColumn('sale_postcode', function ($sale) {
                    return $sale->formatted_postcode; // Using accessor
                })
                ->addColumn('created_at', function ($sale) {
                    return $sale->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($sale) {
                    return $sale->formatted_updated_at; // Using accessor
                })
                ->addColumn('sale_notes', function ($sale) {                   
                    $short = Str::limit(strip_tags($sale->sale_notes), 100);
                    $full = e($sale->sale_notes);
                    $id = 'notes-' . $sale->id;

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
                                        <h5 class="modal-title" id="' . $id . '-label">Notes</h5>
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
                ->addColumn('status', function ($sale)  use ($applicant) {
                    $status_value = 'Open';
                    $status_clr = 'bg-dark';
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->sale_id == $sale->id) {
                            if ($value->status == 1) {
                                $status_value = 'Sent';
                                $status_clr = 'bg-success';
                                break;
                            } elseif ($value->status == 0) {
                                $status_value = 'Reject Job';
                                $status_clr = 'bg-danger';
                                break;
                            } elseif ($value->status == 2) {
                                $status_value = 'Paid';
                                $status_clr = 'bg-success';
                                break;
                            }
                        }
                    }

                    return '<span class="badge '. $status_clr .'">'. $status_value .'</span>';
                })
                ->addColumn('action', function ($sale) use ($applicant) {
                    $status_value = 'open';
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->sale_id == $sale->id) {
                            if ($value->status == 1) {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 0) {
                                $status_value = 'reject_job';
                                break;
                            } elseif ($value->status == 2) {
                                $status_value = 'paid';
                                break;
                            }
                        }
                    }

                    $html = '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">';
                    if ($status_value == 'open') {
                        $html .= '<li><a href="#" onclick="markNotInterestedModal(' . $applicant->id . ', ' . $sale->id . ')" 
                                                        class="dropdown-item">
                                                        Mark Not Interested On Sale
                                                    </a></li>';
                        if ($applicant->is_in_nurse_home == false) {
                            $html .= '<li><a href="#" class="dropdown-item" onclick="markNoNursingHomeModal(' . $applicant->id . ')">
                                                        Mark No Nursing Home</a></li>';
                        }

                        $html .= '<li><a href="#" onclick="sendCVModal(' . $applicant->id . ', ' . $sale->id . ')" class="dropdown-item" >
                                                    <span>Send CV</span></a></li>';

                        if ($applicant->is_callback_enable == false) {
                            $html .= '<li><a href="#" class="dropdown-item"  onclick="markApplicantCallbackModal(' . $applicant->id . ', ' . $sale->id . ')">Mark Callback</a></li>';
                        }
                    } elseif ($status_value == 'sent' || $status_value == 'reject_job' || $status_value == 'paid') {
                        $html .= '<li><a href="#" class="disabled dropdown-item">Locked</a></li>';
                    }

                    $html .= '</ul>
                        </div>';

                    return $html;
                })
                ->rawColumns(['sale_notes', 'paid_status', 'experience', 'cv_limit', 'job_title', 'open_date', 'job_category', 'office_name', 'unit_name', 'status', 'action', 'statusFilter'])
                ->make(true);
        }
    }
    public function getAvailableNoJobs(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $limitCountFilter = $request->input('cv_limit_filter', ''); // Default is empty (no filter)
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)
        $applicant_id = $request->input('applicant_id'); // Default is empty (no filter)
        $radius = $request->input('radius'); // Default is empty (no filter)

        $applicant = Applicant::with('cv_notes')->find($applicant_id);

        $lat = (float) $applicant->lat;
        $lon = (float) $applicant->lng;

        $model = Sale::query()
            ->select([
                'sales.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'units.unit_name as unit_name',
                'users.name as user_name',
                DB::raw("((ACOS(SIN($lat * PI() / 180) * SIN(sales.lat * PI() / 180) + 
                        COS($lat * PI() / 180) * COS(sales.lat * PI() / 180) * COS(($lon - sales.lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
                        AS distance"),
                DB::raw("(SELECT COUNT(*) FROM cv_notes WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1) as no_of_sent_cv")
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->where('sales.status', 1) // Only active sales
            ->whereNotExists(function ($query) use ($applicant_id) {
                $query->select(DB::raw(1))
                    ->from('applicants_pivot_sales')
                    ->whereColumn('applicants_pivot_sales.sale_id', 'sales.id')
                    ->where('applicants_pivot_sales.applicant_id', $applicant_id);
            })
            ->with(['jobTitle', 'jobCategory', 'unit', 'office', 'user']);

        $jobTitle = JobTitle::find($applicant->job_title_id);

        // Decode related_titles safely and normalize
        $relatedTitles = is_array($jobTitle->related_titles)
            ? $jobTitle->related_titles
            : json_decode($jobTitle->related_titles ?? '[]', true);

        // Make sure it's an array, lowercase all, and add main title
        $titles = collect($relatedTitles)
            ->map(fn($item) => strtolower(trim($item)))
            ->push(strtolower(trim($jobTitle->name)))
            ->unique()
            ->values()
            ->toArray();

        $jobTitleIds = JobTitle::whereIn(DB::raw('LOWER(name)'), $titles)->pluck('id')->toArray();

        $model->whereIn('sales.job_title_id', $jobTitleIds);

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    $likeSearch = "%{$searchTerm}%";

                    $query->whereRaw('LOWER(sales.sale_postcode) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.experience) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.timing) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_description) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.position_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.cv_limit) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.salary) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.benefits) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.qualification) LIKE ?', [$likeSearch]);

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($likeSearch) {
                        $q->where('job_titles.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($likeSearch) {
                        $q->where('job_categories.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('unit', function ($q) use ($likeSearch) {
                        $q->where('units.unit_name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('office', function ($q) use ($likeSearch) {
                        $q->where('offices.office_name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('user', function ($q) use ($likeSearch) {
                        $q->where('users.name', 'LIKE', "%{$likeSearch}%");
                    });
                });
            }
        }

        // Filter by status if it's not empty
        switch ($statusFilter) {
            case 'active':
                $model->where('sales.status', 1);
                break;
            case 'closed':
                $model->where('sales.status', 0)
                    ->where('sales.is_on_hold', 0);
                break;
            case 'pending':
                $model->where('sales.status', 2);
                break;
            case 'rejected':
                $model->where('sales.status', 3);
                break;
            case 'on hold':
                $model->where('sales.is_on_hold', true);
                break;
        }

        // Filter by type if it's not empty
        switch ($typeFilter) {
            case 'specialist':
                $model->where('sales.job_type', 'specialist');
                break;
            case 'regular':
                $model->where('sales.job_type', 'regular');
                break;
        }

        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->where('sales.office_id', $officeFilter);
        }

        // Filter by category if it's not empty
        if ($limitCountFilter) {
            $model->where('sales.cv_limit', $limitCountFilter);
        }

        // Filter by category if it's not empty
        if ($categoryFilter) {
            $model->where('sales.job_category_id', $categoryFilter);
        }

        // Filter by category if it's not empty
        if ($titleFilter) {
            $model->where('sales.job_title_id', $titleFilter);
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('sales.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('sales.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('sales.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('sales.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('sales.updated_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('office_name', function ($sale) {
                    $office_id = $sale->office_id;
                    $office = Office::find($office_id);
                    return $office ? $office->office_name : '-';
                })
                ->addColumn('unit_name', function ($sale) {
                    $unit_id = $sale->unit_id;
                    $unit = Unit::find($unit_id);
                    return $unit ? $unit->unit_name : '-';
                })
                ->addColumn('cv_limit', function ($sale) {
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >' . $sale->no_of_sent_cv . '/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int)$sale->cv_limit - (int)$sale->no_of_sent_cv . '/' . (int)$sale->cv_limit) . "<br>Limit Remains</span>";
                    return $status;
                })
                ->addColumn('job_title', function ($sale) {
                    return $sale->jobTitle ? $sale->jobTitle->name : '-';
                })
                ->addColumn('open_date', function ($sale) {
                    return $sale->open_date ? Carbon::parse($sale->open_date)->format('d M Y, h:i A') : '-'; // Using accessor
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? $sale->jobCategory->name . $stype : '-';
                })
                ->addColumn('sale_postcode', function ($sale) {
                    return $sale->formatted_postcode; // Using accessor
                })
                ->addColumn('created_at', function ($sale) {
                    return $sale->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($sale) {
                    return $sale->formatted_updated_at; // Using accessor
                })
                ->addColumn('experience', function ($sale) {
                    $short = Str::limit(strip_tags($sale->experience), 80);
                    $full = e($sale->experience);
                    $id = 'exp-' . $sale->id;

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
                                        <h5 class="modal-title" id="' . $id . '-label">Sale Experience</h5>
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
                ->addColumn('sale_notes', function ($sale) {
                    $notes = nl2br(htmlspecialchars($sale->sale_notes, ENT_QUOTES, 'UTF-8'));
                    $notes = $notes ? $notes : 'N/A';
                    $postcode = htmlspecialchars($sale->sale_postcode, ENT_QUOTES, 'UTF-8');
                    $unit = Unit::find($sale->unit_id);
                    $unit_name = $unit ? $unit->unit_name : '-';
                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . $notes . '\', \'' . $unit_name . '\', \'' . $postcode . '\')">
                                <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                            </a>';
                })
                ->addColumn('status', function ($sale) {
                    $status = '';
                    if ($sale->status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($sale->status == 0 && $sale->is_on_hold == 0) {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($sale->status == 2) {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($sale->status == 3) {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    return $status;
                })
                ->addColumn('paid_status', function ($sale) use ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-success';
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value['status'] == 1) { //active
                            $status_value = 'sent';
                            break;
                        } elseif (($value['status'] == 0) && ($value['sale_id'] == $sale->id)) {
                            $status_value = 'reject_job';
                            $color_class = 'bg-danger';
                            break;
                        } elseif ($value['status'] == 0) { //disable
                            $status_value = 'reject';
                            $color_class = 'bg-danger';
                        } elseif (($value['status'] == 2) && //2 for paid
                            ($value['sale_id'] == $sale->id) &&
                            ($applicant->paid_status == 'open')
                        ) {
                            $status_value = 'paid';
                            $color_class = 'bg-primary';
                            break;
                        }
                    }
                    $status = '';
                    $status .= '<span class="badge ' . $color_class . '">';
                    $status .= ucwords($status_value);
                    $status .= '</span>';

                    return $status;
                })
                ->addColumn('action', function ($sale) use ($applicant) {
                    $status_value = 'open';
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value['status'] == 1) { //active
                            $status_value = 'sent';
                            break;
                        } elseif ($value['status'] == 0) { //disable
                            $status_value = 'reject_job';
                        } elseif ($value['status'] == 2) { //paid
                            $status_value = 'paid';
                            break;
                        }
                    }

                    $html = '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">';
                    if ($status_value == 'open') {
                        $html .= '<li><a href="#" onclick="markNotInterestedModal(' . $applicant->id . ', ' . $sale->id . ')" 
                                                        class="dropdown-item">
                                                        Mark Not Interested On Sale
                                                    </a></li>';
                        if ($applicant->is_in_nurse_home == false) {
                            $html .= '<li><a href="#" class="dropdown-item" onclick="markNoNursingHomeModal(' . $applicant->id . ')">
                                                        Mark No Nursing Home</a></li>';
                        }

                        $html .= '<li><a href="#" onclick="sendCVModal(' . $applicant->id . ', ' . $sale->id . ')" class="dropdown-item" >
                                                    <span>Send CV</span></a></li>';

                        if ($applicant->is_callback_enable == false) {
                            $html .= '<li><a href="#" class="dropdown-item"  onclick="markApplicantCallbackModal(' . $applicant->id . ', ' . $sale->id . ')">Mark Callback</a></li>';
                        }
                    } elseif ($status_value == 'sent' || $status_value == 'reject_job' || $status_value == 'paid') {
                        $html .= '<li><a href="#" class="disabled dropdown-item">Locked</a></li>';
                    }

                    $html .= '</ul>
                        </div>';

                    return $html;
                })
                ->rawColumns(['sale_notes', 'paid_status', 'experience', 'cv_limit', 'job_title', 'open_date', 'job_category', 'office_name', 'unit_name', 'status', 'action', 'statusFilter'])
                ->make(true);
        }
    }
    public function getApplicanCallbackNotes(Request $request)
    {
        try {
            // Validate the incoming request to ensure 'id' is provided and is a valid integer
            $request->validate([
                'id' => 'required',  // Assuming 'module_notes' is the table name and 'id' is the primary key
            ]);

            // Fetch the module notes by the given ID
            $applicant_notes = ApplicantNote::whereIn('moved_tab_to', ['callback', 'revert_callback'])
                ->where('applicant_id', $request->id)
                ->orderBy('id', 'desc')
                ->get();

            // Check if the module note was found
            if (!$applicant_notes) {
                return response()->json(['error' => 'Applicant note not found'], 404);  // Return 404 if not found
            }

            // Return the specific fields you need (e.g., applicant name, notes, etc.)
            return response()->json([
                'data' => $applicant_notes,
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
    public function getApplicantNoNursingHomeNotes(Request $request)
    {
        try {
            // Validate the incoming request to ensure 'id' is provided and is a valid integer
            $request->validate([
                'id' => 'required',  // Assuming 'module_notes' is the table name and 'id' is the primary key
            ]);

            // Fetch the module notes by the given ID
            $applicant_notes = ApplicantNote::whereIn('moved_tab_to', ['no_nursing_home', 'revert_no_nursing_home'])
                ->where('applicant_id', $request->id)
                ->orderBy('id', 'desc')
                ->get();

            // Check if the module note was found
            if (!$applicant_notes) {
                return response()->json(['error' => 'Applicant note not found'], 404);  // Return 404 if not found
            }

            // Return the specific fields you need (e.g., applicant name, notes, etc.)
            return response()->json([
                'data' => $applicant_notes,
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
}
