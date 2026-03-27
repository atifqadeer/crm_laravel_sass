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
        $offices = Office::where('status', 1)->orderBy('office_name','asc')->get();
        return view('units.list', compact('offices'));
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

            $postcode = preg_replace('/\s+/', '', $request->unit_postcode);
            // 1. Try to find a match in the full postcodes table first
            $postcode_query = DB::table('postcodes')
                ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$postcode])
                ->first();

            // 2. Fallback: If not found in full postcodes, check outcodes
            if (!$postcode_query) {
                $postcode_query = DB::table('outcodepostcodes')
                    ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                    ->first();
            }

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
                $unit->contacts()->create($contactData);
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
    public function getUnits(Request $request)
    {
        $statusFilter = $request->input('status_filter');
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)

        $query = Unit::query()
            ->select('units.*', 'offices.office_name as office_name')
            ->leftJoin('offices', 'units.office_id', '=', 'offices.id')
            ->whereNull('units.deleted_at')
            ->with('office', 'contacts');

        if ($statusFilter === 'active') {
            $query->where('units.status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('units.status', 0);
        }

        // Office filter
        if ($officeFilter !== '') {
            $query->whereIn('units.office_id', $officeFilter);
        }

        // ─── Turbo Search Optimization (Units + Contacts) ───────────────────
        if ($request->filled('search.value')) {
            $search = trim($request->input('search.value'));

            if (strlen($search) >= 2) {
                // 1. Scout search matches unit_name, etc.
                $unitIdsFromElastic = Unit::search($search)->keys()->toArray();

                // 2. Search for Units via Office (Scout search on Office model)
                $officeIds = Office::search($search)->keys()->toArray();
                $unitIdsByOffice = Unit::whereIn('office_id', $officeIds)->pluck('id')->toArray();

                // 3. Still do the Contact SQL check
                $contactIds = Contact::where('contactable_id', '>', 0)
                    ->where('contactable_type', 'Horsefly\\Unit')
                    ->where(function ($q) use ($search) {
                        $q->where('contact_email', 'LIKE', "%{$search}%")
                        ->orWhere('contact_phone', 'LIKE', "%{$search}%");
                    })->pluck('contactable_id')->toArray();

                $allIds = array_unique(array_merge($unitIdsFromElastic, $unitIdsByOffice, $contactIds));

                $query->whereIn('units.id', $allIds);
            }
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data");
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                // Handle contact.* columns differently if needed
                // $column = str_starts_with($orderColumn, 'contact.') ? 'contacts.' . str_replace('contact.', '', $orderColumn) : 'units.' . $orderColumn;
                $query->orderBy($orderColumn, $orderDirection);
            } else {
                $query->orderBy('units.created_at', 'desc');
            }
        } else {
            $query->orderBy('units.created_at', 'desc');
        }


        /* -------------------------------------------------
     | DataTables Response
     -------------------------------------------------*/
        return DataTables::eloquent($query)
            ->addIndexColumn()

            ->addColumn('office_name', fn($u) => $u->office?->office_name ?? '-')
            ->filterColumn('office_name', function ($query, $keyword) {
                $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $query->where('offices.office_name', 'LIKE', "%{$word}%");
                }
            })
            ->addColumn('unit_name', fn($u) => $u->formatted_unit_name)
            ->addColumn('unit_postcode', fn($u) => $u->formatted_postcode)
            ->editColumn('unit_postcode', function ($u) {
                $rawPostcode = trim($u->formatted_postcode);
                if (empty($rawPostcode)) return '<div class="text-center w-100">-</div>';

                $postcode = $u->formatted_postcode;
                $copyBtn = '<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 copy-postcode" 
                                data-postcode="' . e($u->formatted_postcode) . '" title="Copy Postcode">
                                <iconify-icon icon="solar:copy-linear" class="fs-18"></iconify-icon>
                            </button>';

                return '<div class="d-flex align-items-center justify-content-between">' . $postcode . $copyBtn . '</div>';
            })
            ->addColumn(
                'contact_email',
                fn($u) =>
                $u->contacts->pluck('contact_email')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_phone',
                fn($u) =>
                $u->contacts->pluck('contact_phone')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_landline',
                fn($u) =>
                $u->contacts->pluck('contact_landline')->filter()->implode('<br>') ?: '-'
            )
            ->filterColumn('contact_email', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereExists(function ($q) use ($keyword) {
                    $q->select(DB::raw(1))
                      ->from('contacts')
                      ->whereColumn('contacts.contactable_id', 'units.id')
                      ->where('contacts.contactable_type', \Horsefly\Unit::class)
                      ->where('contact_email', 'LIKE', "{$keyword}%");
                });
            })
            ->filterColumn('contact_phone', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                      ->from('contacts')
                      ->whereColumn('contacts.contactable_id', 'units.id')
                      ->where('contacts.contactable_type', \Horsefly\Unit::class)
                      ->where('contact_phone', 'LIKE', "{$clean}%");
                });
            })
            ->filterColumn('contact_landline', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                      ->from('contacts')
                      ->whereColumn('contacts.contactable_id', 'units.id')
                      ->where('contacts.contactable_type', \Horsefly\Unit::class)
                      ->where('contact_landline', 'LIKE', "{$clean}%");
                });
            })

            ->addColumn('created_at', fn($u) => $u->formatted_created_at)
            ->addColumn('updated_at', fn($u) => $u->formatted_updated_at)
            ->addColumn(
                'unit_notes',
                fn($u) =>
                '<a href="javascript:void(0);" onclick="addShortNotesModal(' . (int)$u->id . ')">'
                    . nl2br(e($u->unit_notes)) . '</a>'
            )
            ->addColumn(
                'status',
                fn($u) =>
                $u->status
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>'
            )
            ->addColumn('action', function ($u) {
                $postcode    = $u->formatted_postcode;
                $office_name = $u->office?->office_name ?? '-';
                $status      = $u->status
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';

                $html = '<div class="btn-group dropstart">
                        <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                        </button>
                        <ul class="dropdown-menu">';

                if (Gate::allows('unit-edit')) {
                    $html .= '<li><a class="dropdown-item" href="' . route('units.edit', ['id' => $u->id]) . '">Edit</a></li>';
                }
                if (Gate::allows('unit-view')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="showDetailsModal('
                        . (int)$u->id . ', '
                        . '\'' . e($office_name) . '\', '
                        . '\'' . e($u->unit_name) . '\', '
                        . '\'' . e($postcode) . '\', '
                        . '\'' . e($status) . '\')">View</a></li>';
                }
                if (Gate::allows('unit-view-notes-history') || Gate::allows('unit-view-manager-details')) {
                    $html .= '<li><hr class="dropdown-divider"></li>';
                }
                if (Gate::allows('unit-view-notes-history')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="viewNotesHistory(' . $u->id . ')">Notes History</a></li>';
                }
                if (Gate::allows('unit-view-manager-details')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="viewManagerDetails(' . $u->id . ')">Manager Details</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })

            ->rawColumns([
                'unit_notes',
                'contact_email',
                'contact_phone',
                'contact_landline',
                'status',
                'office_name',
                'unit_name',
                'action',
                'unit_postcode'
            ])
            ->make(true);
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
        $offices = Office::where('status', 1)->select('id', 'office_name')->get();
        $unit = Unit::find($id);
        $contacts = Contact::where('contactable_id', $unit->id)
            ->where('contactable_type', 'Horsefly\Unit')
            ->get();

        return view('units.edit', compact('offices', 'unit', 'contacts'));
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

            $postcode = preg_replace('/\s+/', '', $request->unit_postcode);

            if ($postcode != preg_replace('/\s+/', '', $unit->unit_postcode)) {
                // 1. Try to find a match in the full postcodes table first
                $postcode_query = DB::table('postcodes')
                    ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$postcode])
                    ->first();

                // 2. Fallback: If not found in full postcodes, check outcodes
                if (!$postcode_query) {
                    $postcode_query = DB::table('outcodepostcodes')
                        ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                        ->first();
                }

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
                $unit->contacts()->create($contactData);
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
