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
            $office->update(['office_uid' => md5(uniqid($office->id, true))]);

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
    public function getHeadOffices(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)

        $model = Office::query();

        // Filter by status if it's not empty
        switch($statusFilter){
            case 'active':
                $model->where('offices.status', 1);
                break;
            case 'inactive':
                $model->where('offices.status', 0);
                break;
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
                ->addColumn('website', function ($office) {
                    $website = $office->website;
                    return $website ? '<a href="' . e($website) . '" target="_blank">' . e($website) . '</a>' : '-';
                })
                ->addColumn('created_at', function ($office) {
                    return $office->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($office) {
                    return $office->formatted_updated_at; // Using accessor
                })
                ->addColumn('created_at', function ($office) {
                    return $office->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($office) {
                    return $office->formatted_updated_at; // Using accessor
                })
                ->addColumn('office_notes', function ($office) {
                    $notes = nl2br(htmlspecialchars($office->office_notes, ENT_QUOTES, 'UTF-8'));

                    $name = htmlspecialchars($office->office_name, ENT_QUOTES, 'UTF-8');
                    $postcode = htmlspecialchars($office->office_postcode, ENT_QUOTES, 'UTF-8');
                    $notebtn = '';
                    // Tooltip content with additional data-bs-placement and title
                    if(Gate::allows('office-view-note')){
                    $notebtn .= '<a href="#" title="View Note" onclick="showNotesModal(\'' . (int)$office->id . '\',\'' . $notes . '\', \'' . $name . '\', \'' . $postcode . '\')">
                                <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                            </a>';
                    }
                    if(Gate::allows('office-add-note')){
                    $notebtn .= '<a href="#" title="Add Short Note" onclick="addShortNotesModal(\'' . (int)$office->id . '\')">
                                <iconify-icon icon="solar:clipboard-add-linear" class="text-warning fs-24"></iconify-icon>
                            </a>';
                    }
                    return $notebtn;
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

                ->rawColumns(['office_notes', 'status', 'action', 'website'])
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
            ->orderBy('id', 'desc')
            ->update(['status' => 0]);

        // Create new module note
        $moduleNote = ModuleNote::create([
            'details' => $office_notes,
            'module_noteable_id' => $office_id,
            'module_noteable_type' => 'Horsefly\Office',
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $moduleNote_uid = md5($moduleNote->id);
        $moduleNote->update(['module_note_uid' => $moduleNote_uid]);

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

            // Update the applicant with the validated and formatted data
            $office->update($officeData);

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
