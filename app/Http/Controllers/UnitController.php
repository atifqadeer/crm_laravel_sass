<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Unit;
use Horsefly\Office;
use Horsefly\Contact;
use Horsefly\ModuleNote;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Exports\UnitsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\Geocode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use App\Observers\ActionObserver;
use League\Csv\Reader;

class UnitController extends Controller
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
        return view('units.list');
    }
    public function create()
    {
        return view('units.create');
    }
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'office_id' => 'required',
            'unit_name' => 'required|string|max:255',
            'unit_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'unit_notes' => 'required|string|max:255',

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
            $unitData = $request->only([
                'office_id',
                'unit_name',
                'unit_postcode',
                'unit_website',
                'unit_notes',
            ]);

            // Format data for office
            $unitData['user_id'] = Auth::id();

            $postcode = $request->unit_postcode;
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

                    $unitData['lat'] = $result['lat'];
                    $unitData['lng'] = $result['lng'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to locate address: ' . $e->getMessage()
                    ], 400);
                }
            } else {
                $unitData['lat'] = $postcode_query->lat;
                $unitData['lng'] = $postcode_query->lng;
            }

            $unitData['unit_notes'] = $unit_notes = $request->unit_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            $unit = Unit::create($unitData);

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
                $unit->contact()->create($contactData);
            }

            // Generate UID
            $unit->update(['unit_uid' => md5($unit->id)]);

            // Create new module note
            $moduleNote = ModuleNote::create([
                'details' => $unit_notes,
                'module_noteable_id' => $unit->id,
                'module_noteable_type' => 'Horsefly\Unit',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully',
                'redirect' => route('units.list')
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating unit: ' . $e->getMessage());

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
                if($row['head_office'] != 'Select Office'){
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
                    if (!empty($row['unit_postcode'])) {
                        preg_match('/[A-Z]{1,2}[0-9]{1,2}\s*[0-9][A-Z]{2}/i', $row['unit_postcode'], $matches);
                        $cleanPostcode = $matches[0] ?? substr(trim($row['unit_postcode']), 0, 8);
                    }

                    $office_id = $row['head_office'];

                    if($office_id == 'Select Office'){
                        $office_id = 0;
                    }

                    $names = array_map('trim', explode(',', $row['contact_name'] ?? ''));
                    $emails = array_map('trim', explode(',', $row['contact_email'] ?? ''));
                    $phones = array_map('trim', explode(',', $row['contact_phone_number'] ?? ''));
                    $landlines = array_map('trim', explode(',', $row['contact_landline'] ?? ''));

                    $contacts = [];
                    $maxContacts = max(count($names), count($emails), count($phones), count($landlines));

                    for ($i = 0; $i < $maxContacts; $i++) {
                        $contacts[] = [
                            'contact_name'     => $names[$i] ?? 'N/A',
                            'contact_email'    => $emails[$i] ?? 'N/A',
                            'contact_phone'    => isset($phones[$i]) ? preg_replace('/[^0-9]/', '', $phones[$i]) : '0',
                            'contact_landline' => isset($landlines[$i]) ? preg_replace('/[^0-9]/', '', $landlines[$i]) : '0',
                            'contact_note'     => null,
                        ];
                    }

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
                        'unit_uid' => md5($row['id']),
                        'office_id' => $office_id,
                        'user_id' => $row['user_id'] ?? null,
                        'unit_name' => preg_replace('/\s+/', ' ', trim($row['unit_name'] ?? '')),
                        'unit_website' => $row['website'] ?? null,
                        'unit_notes' => $row['units_notes'] ?? null,
                        'lat' => $lat,
                        'lng' => $lng,
                        'unit_postcode' => $cleanPostcode,
                        'status' => isset($row['status']) && strtolower($row['status']) == 'active' ? 1 : 0,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                        'contacts' => $contacts,
                    ];

                    $processedData[] = $processedRow;
                }
            }

            Log::info('Processed data count: ' . count($processedData));

            // Save data to database
            $successfulRows = 0;
            foreach ($processedData as $index => $row) {
                try {
                    $unit = Unit::updateOrCreate(
                        ['id' => $row['id']],
                        array_diff_key($row, ['contact' => ''])
                    );
                    Log::info("Unit created/updated for row " . ($index + 1) . ": ID={$unit->id}");

                    foreach ($row['contacts'] as $contactData) {
                        $unit->contact()->create($contactData);
                    }
                    Log::info("Contact created for unit ID {$unit->id}");
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
    public function getUnits(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)
        ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
        $model = Unit::query()
            ->select([
                'units.*',
                'offices.office_name as office_name'
            ])
            ->leftJoin('offices', 'units.office_id', '=', 'offices.id')
            ->with(['office','contact']);

        // Filter by status if it's not empty
        switch($statusFilter){
            case 'active':
                $model->where('units.status', 1);
                break;
            case 'inactive':
                $model->where('units.status', 0);
                break;
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    $likeSearch = "%{$searchTerm}%";

                    $query->whereRaw('LOWER(units.unit_postcode) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(units.unit_name) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(units.unit_postcode) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(units.unit_website) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(units.created_at) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(contacts.contact_name) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(contacts.contact_email) LIKE ?', [$likeSearch])
                        ->orWhereRaw('contacts.contact_phone LIKE ?', [$likeSearch])
                        ->orWhereRaw('contacts.contact_landline LIKE ?', [$likeSearch]);

                        $query->orWhereHas('office', function ($q) use ($likeSearch) {
                            $q->whereRaw('offices.office_name LIKE ?', [$likeSearch]);
                        });
                });
            }
        }

         // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Default case for valid columns
            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            else {
                $model->orderBy('units.created_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('units.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('office_name', function ($unit) {
                    $office_id = $unit->office_id;
                    $office = Office::find($office_id);
                    return $office ? $office->office_name : '-';
                })
                ->addColumn('unit_name', function ($unit) {
                    return $unit->formatted_unit_name; // Using accessor
                })
                ->addColumn('unit_postcode', function ($unit) {
                    return $unit->formatted_postcode; // Using accessor
                })
                ->addColumn('contact_email', function ($unit) {
                    $contact = $unit->contact;
                    if ($contact && $contact->count() > 0) {
                        $email = [];
                        foreach ($contact as $c) {
                            $email[] = $c->contact_email ? e($c->contact_email) : '-';
                        }
                        return implode('<br>', $email);
                    }
                    return '-';
                })
                ->addColumn('contact_landline', function ($unit) {
                    $contact = $unit->contact;
                    if ($contact && $contact->count() > 0) {
                        $landline = [];
                        foreach ($contact as $c) {
                            $landline[] = $c->contact_landline ? e($c->contact_landline) : '-';
                        }
                        return implode('<br>', $landline);
                    }
                    return '-';
                })
                ->addColumn('contact_phone', function ($unit) {
                    $contact = $unit->contact;
                    if ($contact && $contact->count() > 0) {
                        $phones = [];
                        foreach ($contact as $c) {
                            $phones[] = $c->contact_phone ? e($c->contact_phone) : '-';
                        }
                        return implode('<br>', $phones);
                    }
                    return '-';
                })
                ->addColumn('created_at', function ($unit) {
                    return $unit->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($unit) {
                    return $unit->formatted_updated_at; // Using accessor
                })
                ->addColumn('unit_notes', function ($unit) {
                    $notes = nl2br(htmlspecialchars($unit->unit_notes, ENT_QUOTES, 'UTF-8'));
                    return '
                        <a href="#" title="Add Short Note" style="color:blue" onclick="addShortNotesModal(\'' . (int)$unit->id . '\')">
                            ' . $notes . '
                        </a>
                    ';
                })
                ->addColumn('status', function ($unit) {
                    $status = '';
                    if ($unit->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return $status;
                })
                ->addColumn('action', function ($unit) {
                    $postcode = $unit->formatted_postcode;
                    $office_id = $unit->office_id;
                    $office = Office::find($office_id);
                    $office_name = $office ? $office->office_name : '-';
                    $status = '';

                    if ($unit->status) {
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
                                    if(Gate::allows('unit-edit')){
                                        $html .= '<li><a class="dropdown-item" href="' . route('units.edit', ['id' => $unit->id]) . '">Edit</a></li>';
                                    }
                                    if(Gate::allows('unit-view')){
                                    $html .= '<li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                            ' . (int)$unit->id . ',
                                            \'' . addslashes(htmlspecialchars($office_name)) . '\',
                                            \'' . addslashes(htmlspecialchars($unit->unit_name)) . '\',
                                            \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                            \'' . addslashes(htmlspecialchars($status)) . '\'
                                        )">View</a></li>';
                                    }
                                    if(Gate::allows('unit-view-notes-history') || Gate::allows('unit-view-manager-details')){
                                        $html .= '<li><hr class="dropdown-divider"></li>';
                                    }
                                    if(Gate::allows('unit-view-notes-history')){
                                        $html .= '<li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $unit->id . ')">Notes History</a></li>';
                                    }
                                    if(Gate::allows('unit-view-manager-details')){
                                        $html .= '<li><a class="dropdown-item" href="#" onclick="viewManagerDetails(' . $unit->id . ')">Manager Details</a></li>';
                                    }
                                $html .= '</ul>
                            </div>';
                        return $html;
                })

                ->rawColumns(['unit_notes', 'unit_name', 'contact_email', 'contact_landline', 'contact_phone', 'office_name', 'status', 'action'])
                ->make(true);
        }
    }
    public function storeUnitShortNotes(Request $request)
    {
        $user = Auth::user();

        $unit_id = $request->input('unit_id');
        $details = $request->input('details');
        $unit_notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

        $updateData = ['unit_notes' => $unit_notes];

        Unit::where('id', $unit_id)->update($updateData);

        // Disable previous module note
        ModuleNote::where([
            'module_noteable_id' => $unit_id,
            'module_noteable_type' => 'Horsefly\Unit'
        ])
            ->orderBy('id', 'desc')
            ->update(['status' => 0]);

        // Create new module note
        $moduleNote = ModuleNote::create([
            'details' => $unit_notes,
            'module_noteable_id' => $unit_id,
            'module_noteable_type' => 'Horsefly\Unit',
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $moduleNote->update(['module_note_uid' => md5($moduleNote->id)]);

        // Log audit
        $unit = Unit::where('id', $unit_id)->select('unit_name', 'unit_notes', 'id')->first();
        $observer = new ActionObserver();
        $observer->customUnitAudit($unit, 'unit_notes');

        return redirect()->to(url()->previous());
    }
    public function unitDetails($id)
    {
        $unit = Unit::findOrFail($id);
        return view('units.details', compact('unit'));
    }
    public function edit($id)
    {
        return view('units.edit', compact('office'));
    }
    public function update(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'office_id' => 'required',
            'unit_name' => 'required|string|max:255',
            'unit_postcode' => ['required', 'string', 'min:3', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'unit_notes' => 'required|string|max:255',

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
            $unitData = $request->only([
                'office_id',
                'unit_name',
                'unit_postcode',
                'unit_website',
                'unit_notes',
            ]);

            // Get the office ID from the request
            $id = $request->input('unit_id');

            // Retrieve the office record
            $unit = Unit::find($id);

            // If the applicant doesn't exist, throw an exception
            if (!$unit) {
                throw new \Exception("Unit not found with ID: " . $id);
            }

            $postcode = $request->unit_postcode;

            if($postcode != $unit->unit_postcode){
                if (strlen($postcode) < 6) {
                    // Search in 'outpostcodes' table
                    $postcode_query = DB::table('outcodepostcodes')->where('outcode', $postcode)->first();
                } else {
                    // Search in 'postcodes' table
                    $postcode_query = DB::table('postcodes')->where('postcode', $postcode)->first();
                }

                if ($postcode_query) {
                    $unitData['lat'] = $postcode_query->lat;
                    $unitData['lng'] = $postcode_query->lng;
                }
            }

            $unitData['unit_notes'] = $unit_notes = $request->unit_notes . ' --- By: ' . Auth::user()->name . ' Date: ' . Carbon::now()->format('d-m-Y');

            // Update the applicant with the validated and formatted data
            $unit->update($unitData);

            ModuleNote::where([
                'module_noteable_id' => $id,
                'module_noteable_type' => 'Horsefly\Unit'
            ])
                ->where('status', 1)
                ->update(['status' => 0]);

            $moduleNote = ModuleNote::create([
                'details' => $unit_notes,
                'module_noteable_id' => $unit->id,
                'module_noteable_type' => 'Horsefly\Unit',
                'user_id' => Auth::id()
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            Contact::where('contactable_id', $unit->id)
                ->where('contactable_type', 'Horsefly\Unit')->delete();

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
                $unit->contact()->create($contactData);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully',
                'redirect' => route('units.list')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating unit: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the unit. Please try again.'
            ], 500);
        }
    }
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return redirect()->route('units.list')->with('success', 'Unit deleted successfully');
    }
    public function show($id)
    {
        $unit = Unit::findOrFail($id);
        return view('units.show', compact('unit'));
    }
    public function export(Request $request)
    {
        $type = $request->query('type', 'all'); // Default to 'all' if not provided
        
        return Excel::download(new UnitsExport($type), "units_{$type}.csv");
    }
}
