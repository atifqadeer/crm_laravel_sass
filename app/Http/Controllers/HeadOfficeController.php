<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Office;
use Horsefly\Contact;
use Horsefly\ModuleNote;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Exports\HeadOfficesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\Geocode;
use Illuminate\Support\Facades\Gate;
use App\Observers\ActionObserver;
use Carbon\Carbon;
use League\Csv\Reader;

class HeadOfficeController extends Controller
{
    use Geocode;

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
        return view('head-offices.list');
    }
    public function create()
    {
        return view('head-offices.create');
    }
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'office_name' => 'required|string|max:255',
            'office_type' => 'required',
            'office_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],

            'office_notes' => 'required|string|max:255',

            // Contact person's details (Array validation)
            'contact_name' => 'required|array',
            'contact_name.*' => 'required|string|max:255',

            'contact_email' => 'required|array',
            'contact_email.*' => 'required|email|max:255',

            'contact_phone' => 'nullable|array',
            'contact_phone.*' => 'nullable|string|max:20',

            'contact_landline' => 'nullable|array',
            'contact_landline.*' => 'nullable|string|max:20',

            'contact_note' => 'nullable|array',
            'contact_note.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Please fix the errors in the form'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Get office data
            $officeData = $request->only([
                'office_name',
                'office_type',
                'office_postcode',
                'office_website',
                'office_notes',
            ]);

            $postcode = $request->office_postcode;
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

                    $officeData['office_lat'] = $result['lat'];
                    $officeData['office_lng'] = $result['lng'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to locate address: ' . $e->getMessage()
                    ], 400);
                }
            } else {
                $officeData['office_lat'] = $postcode_query->lat;
                $officeData['office_lng'] = $postcode_query->lng;
            }

            // Format data for office
            $officeData['user_id'] = Auth::id();
            $officeData['office_notes'] = $office_notes = $request->office_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            $office = Office::create($officeData);

            // Iterate through each contact provided in the request
            foreach ($request->input('contact_name') as $index => $contactName) {
                // Create contact data for each contact in the array
                $contactData = [
                    'contact_name' => $contactName,
                    'contact_email' => $request->input('contact_email')[$index],
                    'contact_phone' => preg_replace('/[^0-9]/', '', $request->input('contact_phone')[$index]),
                    'contact_landline' => $request->input('contact_landline')[$index]
                        ? preg_replace('/[^0-9]/', '', $request->input('contact_landline')[$index])
                        : null,
                    'contact_note' => $request->input('contact_note')[$index] ?? null,
                ];

                // Create each contact and associate it with the office
                $office->contact()->create($contactData);
            }

            // Generate UID
            $office->update(['office_uid' => md5($office->id)]);

            // Create new module note
            $moduleNote = ModuleNote::create([
                'details' => $office_notes,
                'module_noteable_id' => $office->id,
                'module_noteable_type' => 'Horsefly\Office',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Head Office created successfully',
                'redirect' => route('head-offices.list')
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating head office: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the head office. Please try again.'
            ], 500);
        }
    }
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,xlsx|max:20480',
        ]);

         ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

        try {
            $file = $request->file('csv_file');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");
            Log::info('File stored at: ' . $filePath);

            // Convert file to UTF-8 if needed
            $content = file_get_contents($filePath);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                file_put_contents($filePath, $content);
            }

            // Load CSV with League\CSV
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // First row is header
            $csv->setDelimiter(','); // Ensure correct delimiter
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $records = $csv->getRecords();
            $headers = $csv->getHeader();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $rowIndex = 1; // Start from 1 to skip header

            foreach ($records as $row) {
                $rowIndex++;
                Log::info("Processing row {$rowIndex}: " . json_encode($row));

                // Pad or truncate row to match header count
                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);

                // Combine headers with row data
                $row = array_combine($headers, $row);
                if ($row === false) {
                    Log::warning("Skipped row {$rowIndex} due to header mismatch.", ['row' => $row]);
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Header mismatch'];
                    continue;
                }

                // Clean and normalize data
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        // Remove extra whitespace and line breaks
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        // Remove non-ASCII characters
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Validate and format dates
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now();
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now();
                    Log::info("Dates for row {$rowIndex}: created_at={$createdAt}, updated_at={$updatedAt}");
                } catch (\Exception $e) {
                    Log::error("Date format error on row {$rowIndex}: " . $e->getMessage() . ', Data: ' . json_encode($row));
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid date format'];
                    continue;
                }

                // Clean postcode (extract valid postcode, e.g., DN16 2AB)
                $cleanPostcode = '0';
                if (!empty($row['office_postcode'])) {
                    preg_match('/[A-Z]{1,2}[0-9]{1,2}\s*[0-9][A-Z]{2}/i', $row['office_postcode'], $matches);
                    $cleanPostcode = $matches[0] ?? substr(trim($row['office_postcode']), 0, 8);
                }

                // Clean phone numbers
                $cleanPhone = !empty($row['office_contact_phone'])
                    ? preg_replace('/[^0-9]/', '', $row['office_contact_phone'])
                    : '0';
                $cleanLandline = !empty($row['office_contact_landline'])
                    ? preg_replace('/[^0-9]/', '', $row['office_contact_landline'])
                    : '0';

                $lat = (is_numeric($row['lat']) ? (float) $row['lat'] : 0.0000);
                $lng = (is_numeric($row['lng']) ? (float) $row['lng'] : 0.0000);

                if ($lat === null && $lng === null || $lat === 'null' && $lng === 'null') {
                    $postcode_query = strlen($cleanPostcode) < 6
                        ? DB::table('outcodepostcodes')->where('outcode', $cleanPostcode)->first()
                        : DB::table('postcodes')->where('postcode', $cleanPostcode)->first();

                    // if (!$postcode_query) {
                    //     try {
                    //         $result = $this->geocode($cleanPostcode);

                    //         // If geocode fails, throw
                    //         if (!isset($result['lat']) || !isset($result['lng'])) {
                    //             throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                    //         }

                    //         $applicantData['lat'] = $result['lat'];
                    //         $applicantData['lng'] = $result['lng'];
                    //     } catch (\Exception $e) {
                    //         return response()->json([
                    //             'success' => false,
                    //             'message' => 'Unable to locate address: ' . $e->getMessage()
                    //         ], 400);
                    //     }
                    // } else {
                        $lat = $postcode_query->lat;
                        $lng = $postcode_query->lng;
                    // }

                    /** ✅ Validate lat/lng presence before inserting */
                    // if (empty($applicantData['lat']) || empty($applicantData['lng'])) {
                    //     return response()->json([
                    //         'success' => false,
                    //         'message' => 'Postcode location is required. Please provide a valid postcode.'
                    //     ], 400);
                    // }

                }

                // Keep whitespace intact
                if (strlen($cleanPostcode) === 8) {
                    $exists = DB::table('postcodes')->where('postcode', $cleanPostcode)->exists();

                    if (!$exists) {
                        DB::table('postcodes')->insert([
                            'postcode'   => $cleanPostcode,
                            'lat'        => $lat,
                            'lng'        => $lng,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                } elseif (strlen($cleanPostcode) < 6) {
                    $exists = DB::table('outcodepostcodes')->where('outcode', $cleanPostcode)->exists();

                    if (!$exists) {
                        DB::table('outcodepostcodes')->insert([
                            'outcode'    => $cleanPostcode,
                            'lat'        => $lat,
                            'lng'        => $lng,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'office_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'office_name' => preg_replace('/\s+/', ' ', trim($row['office_name'] ?? '')),
                    'office_type' => 'head_office',
                    'office_website' => $row['office_website'] ?? null,
                    'office_notes' => $row['office_notes'] ?? null,
                    'office_lat' => $lat,
                    'office_lng' => $lng,
                    'office_postcode' => $cleanPostcode,
                    'status' => isset($row['status']) && strtolower($row['status']) == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'contact' => [
                        'contact_name' => $row['office_contact_name'] ?? 'N/A',
                        'contact_email' => $row['office_email'] ?? 'N/A',
                        'contact_phone' => $cleanPhone,
                        'contact_landline' => $cleanLandline,
                        'contact_note' => null,
                    ],
                ];

                $processedData[] = $processedRow;
            }

            Log::info('Processed data count: ' . count($processedData));

            // Save data to database
            $successfulRows = 0;
            foreach ($processedData as $index => $row) {
                try {
                    $office = Office::updateOrCreate(
                        ['id' => $row['id']],
                        array_diff_key($row, ['contact' => ''])
                    );
                    Log::info("Office created/updated for row " . ($index + 1) . ": ID={$office->id}");

                    $contactData = $row['contact'];
                    $office->contact()->create($contactData);
                    Log::info("Contact created for office ID {$office->id}");
                    $successfulRows++;
                } catch (\Exception $e) {
                    Log::error("Failed to save row " . ($index + 1) . ": " . $e->getMessage() . ', Data: ' . json_encode($row));
                    $failedRows[] = ['row' => $index + 1, 'error' => $e->getMessage()];
                }
            }

            // Prepare response with summary
            $response = [
                'message' => 'CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('CSV import failed: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the CSV.'], 500);
        }
    }
    public function getHeadOffices(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)

        $model = Office::query()->with('contact');

        // Filter by status if it's not empty
        switch($statusFilter){
            case 'active':
                $model->where('offices.status', 1);
                break;
            case 'inactive':
                $model->where('offices.status', 0);
                break;
        }

        if ($request->has('search.value')) {
            $searchTerm = strtolower($request->input('search.value'));

            $model->where(function ($query) use ($searchTerm) {
                $like = '%' . $searchTerm . '%';

                $query->whereRaw('LOWER(office_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(office_postcode) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(office_type) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(office_notes) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(created_at) LIKE ?', [$like]);
            });
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');


            // Default case for valid columns
            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('offices.created_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('offices.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('office_name', function ($office) {
                    return $office->formatted_office_name; // Using accessor
                })
                ->addColumn('office_postcode', function ($office) {
                    return $office->formatted_postcode; // Using accessor
                })
                ->addColumn('office_type', function ($office) {
                    return ucwords(str_replace('_',' ',$office->office_type)); // Using accessor
                })
                ->addColumn('contact_email', function ($office) {
                    $contact = $office->contact;
                    if ($contact && $contact->count() > 0) {
                        $email = [];
                        foreach ($contact as $c) {
                            $email[] = $c->contact_email ? e($c->contact_email) : '-';
                        }
                        return implode('<br>', $email);
                    }
                    return '-';
                })
                ->addColumn('contact_landline', function ($office) {
                    $contact = $office->contact;
                    if ($contact && $contact->count() > 0) {
                        $landline = [];
                        foreach ($contact as $c) {
                            $landline[] = $c->contact_landline ? e($c->contact_landline) : '-';
                        }
                        return implode('<br>', $landline);
                    }
                    return '-';
                })
                ->addColumn('contact_phone', function ($office) {
                    $contact = $office->contact;
                    if ($contact && $contact->count() > 0) {
                        $phones = [];
                        foreach ($contact as $c) {
                            $phones[] = $c->contact_phone ? e($c->contact_phone) : '-';
                        }
                        return implode('<br>', $phones);
                    }
                    return '-';
                })
                ->addColumn('updated_at', function ($office) {
                    return $office->formatted_updated_at; // Using accessor
                })
                ->addColumn('created_at', function ($office) {
                    return $office->formatted_created_at; // Using accessor
                })
                ->addColumn('office_notes', function ($office) {
                    $notes = nl2br(htmlspecialchars($office->office_notes, ENT_QUOTES, 'UTF-8'));
                    return '
                        <a href="#" title="Add Short Note" style="color:blue" onclick="addShortNotesModal(\'' . (int)$office->id . '\')">
                            ' . $notes . '
                        </a>
                    ';
                })
                ->addColumn('status', function ($office) {
                    $status = '';
                    if ($office->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return $status;
                })
                ->addColumn('action', function ($office) {
                    $postcode = $office->formatted_postcode;
                    $status = '';

                    if ($office->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }
                    $html = '';
                    $html .= '<div class="btn-group dropstart">
                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu">';
                                if(Gate::allows('office-edit')){
                                    $html .= '<li><a class="dropdown-item" href="' . route('head-offices.edit', ['id' => $office->id]) . '">Edit</a></li>';
                                }
                                if(Gate::allows('office-view')){
                                    $html .= '<li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                        ' . (int)$office->id . ',
                                        \'' . addslashes(htmlspecialchars($office->office_name)) . '\',
                                        \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                        \'' . addslashes(htmlspecialchars($status)) . '\'
                                    )">View</a></li>';
                                }
                                if(Gate::allows('office-view-notes-history') || Gate::allows('office-view-manager-details')){
                                    $html .= '<li><hr class="dropdown-divider"></li>';
                                }
                                if(Gate::allows('office-view-notes-history')){   
                                    $html .= '<li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $office->id . ')">Notes History</a></li>';
                                }
                                if(Gate::allows('office-view-manager-details')){
                                    $html .= '<li><a class="dropdown-item" href="#" onclick="viewManagerDetails(' . $office->id . ')">Manager Details</a></li>';
                                }
                               $html .= '</ul>
                            </div>';

                    return $html;
                })

                ->rawColumns(['office_notes', 'contact_email', 'contact_phone', 'contact_landline', 'office_type', 'status', 'action', 'website'])
                ->make(true);
        }
    }
    public function storeHeadOfficeShortNotes(Request $request)
    {
        $user = Auth::user();

        $office_id = $request->input('office_id');
        $details = $request->input('details');
        $office_notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

        $updateData = ['office_notes' => $office_notes];

        Office::where('id', $office_id)->update($updateData);

        // Disable previous module note
        ModuleNote::where([
                'module_noteable_id' => $office_id,
                'module_noteable_type' => 'Horsefly\Office'
            ])
            ->where('status', 1)
            ->update(['status' => 0]);

        // Create new module note
        $moduleNote = ModuleNote::create([
            'details' => $office_notes,
            'module_noteable_id' => $office_id,
            'module_noteable_type' => 'Horsefly\Office',
            'user_id' => $user->id,
        ]);

        $moduleNote->update(['module_note_uid' => md5($moduleNote->id)]);

        // Log audit
        $office = Office::where('id', $office_id)->select('office_name', 'office_notes', 'id')->first();
        $observer = new ActionObserver();
        $observer->customOfficeAudit($office, 'office_notes');

        return redirect()->to(url()->previous());
    }
    public function officeDetails($id)
    {
        $office = Office::findOrFail($id);
        return view('head-offices.details', compact('office'));
    }
    public function edit($id)
    {
        // Debug the incoming id
        Log::info('Trying to edit head office with ID: ' . $id);

        $office = Office::find($id);

        // Check if the applicant is found
        if (!$office) {
            Log::info('Head Office not found with ID: ' . $id);
        }

        return view('head-offices.edit', compact('office'));
    }
    public function update(Request $request)
    {
         // Validation
        $validator = Validator::make($request->all(), [
            'office_name' => 'required|string|max:255',
            'office_type' => 'required',
            'office_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'office_notes' => 'required|string|max:255',

            // Contact person's details (Array validation)
            'contact_name' => 'required|array',
            'contact_name.*' => 'required|string|max:255',

            'contact_email' => 'required|array',
            'contact_email.*' => 'required|email|max:255',

            'contact_phone' => 'nullable|array',
            'contact_phone.*' => 'nullable|string|max:20',

            'contact_landline' => 'nullable|array',
            'contact_landline.*' => 'nullable|string|max:20',

            'contact_note' => 'nullable|array',
            'contact_note.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Please fix the errors in the form'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get office data
            $officeData = $request->only([
                'office_name',
                'office_type',
                'office_postcode',
                'office_website',
                'office_notes',
            ]);

            // Get the office ID from the request
            $id = $request->input('office_id');

            // Retrieve the office record
            $office = Office::find($id);

             // If the applicant doesn't exist, throw an exception
             if (!$office) {
                throw new \Exception("Head Office not found with ID: " . $id);
            }

            $officeData['office_notes'] = $office_notes = $request->office_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            $postcode = $request->office_postcode;

            if($postcode != $office->office_postcode){
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

                        $officeData['office_lat'] = $result['lat'];
                        $officeData['office_lng'] = $result['lng'];
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unable to locate address: ' . $e->getMessage()
                        ], 400);
                    }
                } else {
                    $officeData['office_lat'] = $postcode_query->lat;
                    $officeData['office_lng'] = $postcode_query->lng;
                }
            }

            // Update the Office with the validated and formatted data
            $office->update($officeData);

            ModuleNote::where([
                    'module_noteable_id' => $id,
                    'module_noteable_type' => 'Horsefly\Office'
                ])
                ->where('status', 1)
                ->update(['status' => 0]);

            $moduleNote = ModuleNote::create([
                'details' => $office_notes,
                'module_noteable_id' => $office->id,
                'module_noteable_type' => 'Horsefly\Office',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            Contact::where('contactable_id',$office->id)
                ->where('contactable_type','Horsefly\Office')->delete();

            // Iterate through each contact provided in the request
            foreach ($request->input('contact_name') as $index => $contactName) {
                // Create contact data for each contact in the array
                $contactData = [
                    'contact_name' => $contactName,
                    'contact_email' => $request->input('contact_email')[$index],
                    'contact_phone' => preg_replace('/[^0-9]/', '', $request->input('contact_phone')[$index]),
                    'contact_landline' => $request->input('contact_landline')[$index]
                        ? preg_replace('/[^0-9]/', '', $request->input('contact_landline')[$index])
                        : null,
                    'contact_note' => $request->input('contact_note')[$index] ?? null,
                ];

                // Create each contact and associate it with the office
                $office->contact()->create($contactData);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Head Office updated successfully',
                'redirect' => route('head-offices.list')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating head office: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the head office. Please try again.'
            ], 500);
        }
    }
    public function destroy($id)
    {
        $office = Office::findOrFail($id);
        $office->delete();
        return redirect()->route('head-offices.list')->with('success', 'Head Office deleted successfully');
    }
    public function show($id)
    {
        $office = Office::findOrFail($id);
        return view('head-offices.show', compact('office'));
    }
    public function getModuleContacts(Request $request)
    {
         try {
            // Validate the incoming request to ensure 'id' is provided and is a valid integer
            $request->validate([
                'id' => 'required',  // Assuming 'module_notes' is the table name and 'id' is the primary key
            ]);

            // Fetch the module notes by the given ID
            $contacts = Contact::where('contactable_id', $request->id)->where('contactable_type', $request->module)->latest()->get();

            // Check if the module note was found
            if (!$contacts) {
                return response()->json(['error' => 'Manager Details not found'], 404);  // Return 404 if not found
            }

            // Return the specific fields you need (e.g., applicant name, notes, etc.)
            return response()->json([
                'data' => $contacts,
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
    public function export(Request $request)
    {
        $type = $request->query('type', 'all'); // Default to 'all' if not provided
        
        return Excel::download(new HeadOfficesExport($type), "headOffices_{$type}.csv");
    }
}
