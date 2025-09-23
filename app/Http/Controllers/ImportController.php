<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\Applicant;
use Horsefly\User;
use Horsefly\Message;
use Horsefly\ApplicantNote;
use Horsefly\ModuleNote;
use Horsefly\CrmRejectedCv;
use Horsefly\EmailTemplate;
use Horsefly\IPAddress;
use Horsefly\Interview;
use Horsefly\Region;
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
use Horsefly\Audit;
use Horsefly\CrmNote;
use Horsefly\QualityNotes;
use Horsefly\RevertStage;
use Horsefly\SaleDocument;
use Horsefly\SaleNote;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Spatie\PdfToText\Pdf;
use PhpOffice\PhpWord\IOFactory;


class ImportController extends Controller
{
    public function importIndex()
    {
        return view('settings.import');
    }
    public function applicantsImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv', // Restrict to CSV
        ]);

        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5G');

        try {
            Log::channel('daily')->info('Starting CSV import process.');

            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");
            Log::channel('daily')->info("File stored at: {$filePath}");

            // Convert file to UTF-8 if needed
            $content = file_get_contents($filePath);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding === false) {
                Log::channel('daily')->error("Failed to detect encoding for CSV: {$filePath}");
                throw new \Exception('Unable to detect CSV encoding.');
            }
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                file_put_contents($filePath, $content);
                Log::channel('daily')->info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Load CSV with League\CSV
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::channel('daily')->info("Headers: " . json_encode($headers) . ", Count: {$expectedColumnCount}");

            $processedData = [];
            $failedRows = [];
            $rowIndex = 1;

            foreach ($records as $row) {
                $rowIndex++;
                
                Log::channel('daily')->debug("Processing row {$rowIndex}: " . json_encode($row));

                // Validate required fields
                if (empty($row['job_category']) || empty($row['applicant_job_title'])) {
                    Log::channel('daily')->warning("Skipped row {$rowIndex}: Missing job_category or applicant_job_title");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Missing job_category or applicant_job_title'];
                    continue;
                }

                // Ensure row matches header count
                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false) {
                    Log::channel('daily')->warning("Skipped row {$rowIndex} due to header mismatch");
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

                // Preprocess malformed date strings (e.g., '8282019 753' -> '8/28/2019 7:53')
                $preprocessDate = function ($dateString, $field, $rowIndex) {
                    if (empty($dateString) || !is_string($dateString)) {
                        return null;
                    }
                    // Handle malformed formats like '8282019 753' (mmddyyyy hhmm) or '712019 1153' (mdyyyy hhmm)
                    if (preg_match('/^(\d{1,2})(\d{2})(\d{4})\s(\d{1,2})(\d{2})$/', $dateString, $matches)) {
                        $fixedDate = "{$matches[1]}/{$matches[2]}/{$matches[3]} {$matches[4]}:{$matches[5]}";
                        Log::channel('daily')->debug("Row {$rowIndex}: Fixed malformed {$field} from '{$dateString}' to '{$fixedDate}'");
                        return $fixedDate;
                    } elseif (preg_match('/^(\d{1})(\d{1})(\d{4})\s(\d{1,2})(\d{2})$/', $dateString, $matches)) {
                        $fixedDate = "{$matches[1]}/{$matches[2]}/{$matches[3]} {$matches[4]}:{$matches[5]}";
                        Log::channel('daily')->debug("Row {$rowIndex}: Fixed malformed {$field} from '{$dateString}' to '{$fixedDate}'");
                        return $fixedDate;
                    }
                    return $dateString;
                };

                // Parse dates with multiple formats
                $parseDate = function ($dateString, $rowIndex, $field = 'created_at') {
                    $formats = ['m/d/Y H:i', 'm/d/Y', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d'];

                    foreach ($formats as $format) {
                        try {
                            return Carbon::createFromFormat($format, $dateString)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            Log::debug("Row {$rowIndex}: Failed to parse {$field} '{$dateString}' with format {$format}");
                        }
                    }

                    try {
                        return Carbon::parse($dateString)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        Log::debug("Row {$rowIndex}: Final fallback failed for {$field}: {$e->getMessage()}");
                        return null;
                    }
                };

                $fixedCreatedAt = $preprocessDate($row['created_at'], 'created_at', $rowIndex);
                $fixedUpdatedAt = $preprocessDate($row['updated_at'], 'updated_at', $rowIndex);
                $createdAt = $parseDate($fixedCreatedAt, 'created_at', $rowIndex) ?? now()->format('Y-m-d H:i:s');
                $updatedAt = $parseDate($fixedUpdatedAt, 'updated_at', $rowIndex) ?? now()->format('Y-m-d H:i:s');
                
                $paid_timestamp = null;
                if (!empty($row['paid_timestamp']) && $row['paid_timestamp'] !== 'NULL') {
                    $fixedPaidTimestamp = $preprocessDate($row['paid_timestamp'], 'paid_timestamp', $rowIndex);
                    $paid_timestamp = $parseDate($fixedPaidTimestamp, 'paid_timestamp', $rowIndex);
                }

                // Handle postcode and geolocation
                $cleanPostcode = '0';
                if (!empty($row['applicant_postcode']) && is_string($row['applicant_postcode'])) {
                    preg_match('/[A-Z]{1,2}[0-9]{1,2}\s*[0-9][A-Z]{2}/i', $row['applicant_postcode'], $matches);
                    $cleanPostcode = $matches[0] ?? substr(trim($row['applicant_postcode']), 0, 8);
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

                // Handle job category and title
                $job_category_id = null;
                $job_title_id = null;
                $job_type = '';

                $specialists = [
                    [
                        'id' => 1,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Psychiatrist'
                    ],
                    [
                        'id' => 2,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Spa Therapists'
                    ],
                    [
                        'id' => 3,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Housekeeper'
                    ],
                    [
                        'id' => 4,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'chef de partie'
                    ],
                    [
                        'id' => 5,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Waiter'
                    ],
                    [
                        'id' => 6,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Receptionist'
                    ],
                    [
                        'id' => 7,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Food & Beverage Assistant'
                    ],
                    [
                        'id' => 8,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Commis Chef'
                    ],
                    [
                        'id' => 9,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Occupational Therapist'
                    ],
                    [
                        'id' => 10,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Kitchen Porter'
                    ],
                    [
                        'id' => 11,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Physiotherapist'
                    ],
                    [
                        'id' => 12,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Restaurant Manager'
                    ],
                    [
                        'id' => 13,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Character Breakfast Assistant (C&B)'
                    ],
                    [
                        'id' => 14,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Speech and Language Therapy'
                    ],
                    [
                        'id' => 15,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Ancillary'
                    ],
                    [
                        'id' => 16,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sous Chef'
                    ],
                    [
                        'id' => 17,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pastry Chef'
                    ],
                    [
                        'id' => 18,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Psychologist'
                    ],
                    [
                        'id' => 19,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Manager'
                    ],
                    [
                        'id' => 20,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Radiographer'
                    ],
                    [
                        'id' => 21,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'ODP'
                    ],
                    [
                        'id' => 22,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'BREAST CARE NURSE'
                    ],
                    [
                        'id' => 23,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Andrology Clinical Nurse Specialist'
                    ],
                    [
                        'id' => 25,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Senior Chef De Partie'
                    ],
                    [
                        'id' => 26,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Cleaner'
                    ],
                    [
                        'id' => 27,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Kitchen Assistant'
                    ],
                    [
                        'id' => 28,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Therapist'
                    ],
                    [
                        'id' => 29,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Therapist'
                    ],
                    [
                        'id' => 30,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pharmacist'
                    ],
                    [
                        'id' => 31,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Dining Room Assistant'
                    ],
                    [
                        'id' => 32,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Maintenance'
                    ],
                    [
                        'id' => 33,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Resident Liaison'
                    ],
                    [
                        'id' => 34,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Cook'
                    ],
                    [
                        'id' => 35,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Domestic'
                    ],
                    [
                        'id' => 36,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Doctor'
                    ],
                    [
                        'id' => 37,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Lead Family therapist'
                    ],
                    [
                        'id' => 38,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Administrator'
                    ],
                    [
                        'id' => 39,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'General Chef'
                    ],
                    [
                        'id' => 40,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Group Financial Controller'
                    ],
                    [
                        'id' => 41,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Restaurant Supervisor'
                    ],
                    [
                        'id' => 42,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Front of House'
                    ],
                    [
                        'id' => 43,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Bar Manager'
                    ],
                    [
                        'id' => 44,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Assistant Manager'
                    ],
                    [
                        'id' => 46,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Chef'
                    ],
                    [
                        'id' => 47,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Manager'
                    ],
                    [
                        'id' => 48,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Residential Children’s Worker'
                    ],
                    [
                        'id' => 49,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Practice Educator'
                    ],
                    [
                        'id' => 50,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Endoscopy'
                    ],
                    [
                        'id' => 51,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Nurse Associate'
                    ],
                    [
                        'id' => 52,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Director'
                    ],
                    [
                        'id' => 53,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Scrub Theatre Nurse'
                    ],
                    [
                        'id' => 54,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Anaesthetics lead'
                    ],
                    [
                        'id' => 55,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'AMBULATORY, RECOVERY AND WOUNDCARE'
                    ],
                    [
                        'id' => 56,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Radiographer'
                    ],
                    [
                        'id' => 57,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Bar'
                    ],
                    [
                        'id' => 58,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Radiographer'
                    ],
                    [
                        'id' => 59,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Service Delivery Lead'
                    ],
                    [
                        'id' => 60,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Catering Assistant'
                    ],
                    [
                        'id' => 62,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Accountant'
                    ],
                    [
                        'id' => 63,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Pre-Assessment Nurse'
                    ],
                    [
                        'id' => 64,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Reservations'
                    ],
                    [
                        'id' => 65,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Laundry Assistant'
                    ],
                    [
                        'id' => 66,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Storekeeper'
                    ],
                    [
                        'id' => 67,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Deputy Head Greenkeeper'
                    ],
                    [
                        'id' => 68,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Event Executive'
                    ],
                    [
                        'id' => 69,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sales & Billing administrator'
                    ],
                    [
                        'id' => 70,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Associate Specialist'
                    ],
                    [
                        'id' => 71,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Junior Sous Chef'
                    ],
                    [
                        'id' => 72,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Receptionist'
                    ],
                    [
                        'id' => 73,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Bar Supervisor'
                    ],
                    [
                        'id' => 74,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sales Assistant'
                    ],
                    [
                        'id' => 75,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Guest Service Manager'
                    ],
                    [
                        'id' => 76,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Reservations Office Manager'
                    ],
                    [
                        'id' => 77,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Chef de Rang'
                    ],
                    [
                        'id' => 78,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Junior Sous Chef'
                    ],
                    [
                        'id' => 79,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Purchase Accounting Assistant'
                    ],
                    [
                        'id' => 80,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Floor Attendant'
                    ],
                    [
                        'id' => 81,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'AUDITOR'
                    ],
                    [
                        'id' => 82,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Executive Housekeeper'
                    ],
                    [
                        'id' => 83,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Breakfast Chef'
                    ],
                    [
                        'id' => 84,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Demi Chef de Partie'
                    ],
                    [
                        'id' => 85,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pastry Sous Chef'
                    ],
                    [
                        'id' => 86,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Executive Chef'
                    ],
                    [
                        'id' => 87,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Housekeeping Supervisor'
                    ],
                    [
                        'id' => 88,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'supervisor'
                    ],
                    [
                        'id' => 89,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Duty Manager'
                    ],
                    [
                        'id' => 90,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Clinical Psychologist'
                    ],
                    [
                        'id' => 91,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Food Runner'
                    ],
                    [
                        'id' => 92,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'FRONT OFFICE GSA (GUEST SERVICE ASSOCIATE)'
                    ],
                    [
                        'id' => 93,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Executive Sous Chef'
                    ],
                    [
                        'id' => 94,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'RECEPTION MANAGER'
                    ],
                    [
                        'id' => 95,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'PASTRY CHEF DE PARTIE'
                    ],
                    [
                        'id' => 96,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Breakfast Supervisor'
                    ],
                    [
                        'id' => 97,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Preparation Chef'
                    ],
                    [
                        'id' => 98,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Junior Chef de Partie'
                    ],
                    [
                        'id' => 99,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Assistant Sommelier'
                    ],
                    [
                        'id' => 100,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Food and Beverage Supervisor'
                    ],
                    [
                        'id' => 101,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Breakfast Manager'
                    ],
                    [
                        'id' => 102,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Assistant Barn Manager'
                    ],
                    [
                        'id' => 103,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Senior Chef de Partie'
                    ],
                    [
                        'id' => 104,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Food and Beverage Manager'
                    ],
                    [
                        'id' => 105,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Restaurant Supervisor'
                    ],
                    [
                        'id' => 106,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'SENIOR SOUS CHEF'
                    ],
                    [
                        'id' => 107,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Greenkeeper'
                    ],
                    [
                        'id' => 108,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'CONFERENCE AND BANQUETING MANAGER'
                    ],
                    [
                        'id' => 109,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Team Leader'
                    ],
                    [
                        'id' => 110,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Account Manager'
                    ],
                    [
                        'id' => 111,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Conference and Banqueting Supervisor'
                    ],
                    [
                        'id' => 112,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Barista'
                    ],
                    [
                        'id' => 113,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Baker'
                    ],
                    [
                        'id' => 114,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Chief Steward'
                    ],
                    [
                        'id' => 115,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Porter'
                    ],
                    [
                        'id' => 116,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sushi Chef'
                    ],
                    [
                        'id' => 117,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Brasserie Demi Chef'
                    ],
                    [
                        'id' => 118,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Waiter'
                    ],
                    [
                        'id' => 119,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Restaurant Host'
                    ],
                    [
                        'id' => 120,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Occupational Therapist'
                    ],
                    [
                        'id' => 122,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Meeting and Events Sales Manager'
                    ],
                    [
                        'id' => 123,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Staff Nurse - Anaesthetics'
                    ],
                    [
                        'id' => 124,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Outpatient Sister'
                    ],
                    [
                        'id' => 128,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Marketing Executive'
                    ],
                    [
                        'id' => 129,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Recovery Nurse'
                    ],
                    [
                        'id' => 130,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Assessment Nurse'
                    ],
                    [
                        'id' => 131,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Hospitality Assistant'
                    ],
                    [
                        'id' => 132,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Handy Person'
                    ],
                    [
                        'id' => 133,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Golf Associate'
                    ],
                    [
                        'id' => 134,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Golf Starter & Marshall'
                    ],
                    [
                        'id' => 135,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Runner Linen'
                    ],
                    [
                        'id' => 136,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Guest Services Assistant'
                    ],
                    [
                        'id' => 137,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Bartender'
                    ],
                    [
                        'id' => 138,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Sommelier'
                    ],
                    [
                        'id' => 139,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'PHP Developer'
                    ],
                    [
                        'id' => 140,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Meeting & Events Coordinator'
                    ],
                    [
                        'id' => 141,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Front-End Developer'
                    ],
                    [
                        'id' => 142,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Principal Software Engineer'
                    ],
                    [
                        'id' => 143,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'SEO Manager'
                    ],
                    [
                        'id' => 144,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'General Assistant'
                    ],
                    [
                        'id' => 145,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Senior Software Developer'
                    ],
                    [
                        'id' => 146,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'UI Designer'
                    ],
                    [
                        'id' => 147,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pharmacist Manager'
                    ],
                    [
                        'id' => 148,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Lead Pharmacist'
                    ],
                    [
                        'id' => 149,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Dispensing Assistant'
                    ],
                    [
                        'id' => 150,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pharmacy Dispenser'
                    ],
                    [
                        'id' => 151,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Diner Manager'
                    ],
                    [
                        'id' => 152,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Casual F and B events Sup'
                    ],
                    [
                        'id' => 153,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Psychotherapist'
                    ],
                    [
                        'id' => 154,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Optometrists'
                    ],
                    [
                        'id' => 155,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Dispensing Opticians'
                    ],
                    [
                        'id' => 156,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Psychologist'
                    ],
                    [
                        'id' => 157,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Clinical Psychologist'
                    ],
                    [
                        'id' => 158,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Psychiatrist'
                    ],
                    [
                        'id' => 159,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Psychotherapist'
                    ],
                    [
                        'id' => 160,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Family Therapist'
                    ],
                    [
                        'id' => 161,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Occupational Therapist Assistant'
                    ],
                    [
                        'id' => 162,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Forestry Key Account Manager'
                    ],
                    [
                        'id' => 163,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Project Manager'
                    ],
                    [
                        'id' => 164,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Full Stack Developer'
                    ],
                    [
                        'id' => 165,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Senior Mechanical Engineer'
                    ],
                    [
                        'id' => 166,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Design Engineer'
                    ],
                    [
                        'id' => 167,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Technical Author'
                    ],
                    [
                        'id' => 168,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Solar PV Technical Design Engineer'
                    ],
                    [
                        'id' => 169,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Lead Nurse Infection Control'
                    ],
                    [
                        'id' => 170,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Physiologist'
                    ],
                    [
                        'id' => 171,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Surgical First Assistant'
                    ],
                    [
                        'id' => 172,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Sister'
                    ],
                    [
                        'id' => 173,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Manager'
                    ],
                    [
                        'id' => 174,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sommelier'
                    ],
                    [
                        'id' => 175,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Specialist Support'
                    ],
                    [
                        'id' => 176,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'HR Advisor'
                    ],
                    [
                        'id' => 177,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Business Development'
                    ],
                    [
                        'id' => 178,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Kitchen Manager'
                    ],
                    [
                        'id' => 179,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head of Digital Transformation and IT'
                    ],
                    [
                        'id' => 180,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Housekeeping Manager'
                    ],
                    [
                        'id' => 181,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Hotel General Manager'
                    ],
                    [
                        'id' => 182,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pharmacy Assistant'
                    ],
                    [
                        'id' => 183,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Training Officer'
                    ],
                    [
                        'id' => 184,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pizza Chef'
                    ],
                    [
                        'id' => 185,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Dentist'
                    ],
                    [
                        'id' => 186,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Associate Dentist'
                    ],
                    [
                        'id' => 187,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Hygienist'
                    ],
                    [
                        'id' => 188,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Optical Assistant'
                    ],
                    [
                        'id' => 189,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head of Facilities'
                    ],
                    [
                        'id' => 190,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Dental Nurse'
                    ],
                    [
                        'id' => 191,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Medical Director'
                    ],
                    [
                        'id' => 192,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Positive Behaviour Practitioner'
                    ],
                    [
                        'id' => 193,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Governance & Quality Assurance Lead'
                    ],
                    [
                        'id' => 194,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pharmacy technician'
                    ],
                    [
                        'id' => 195,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Dietician'
                    ],
                    [
                        'id' => 196,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head of Education'
                    ],
                    [
                        'id' => 197,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Orthodontist'
                    ],
                    [
                        'id' => 198,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Prosthodontist'
                    ],
                    [
                        'id' => 199,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Assistant'
                    ],
                    [
                        'id' => 200,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Hotel Manager'
                    ],
                    [
                        'id' => 201,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head of Quality Assurance and Improvement'
                    ],
                    [
                        'id' => 202,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'RGN'
                    ],
                    [
                        'id' => 203,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Operations Manager'
                    ],
                    [
                        'id' => 204,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Operations Manager'
                    ],
                    [
                        'id' => 205,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Practice Leader'
                    ],
                    [
                        'id' => 206,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Scrub Nurse'
                    ],
                    [
                        'id' => 209,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Creche support worker'
                    ],
                    [
                        'id' => 210,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Events and Sales Coordinator'
                    ],
                    [
                        'id' => 211,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Spa Manager'
                    ],
                    [
                        'id' => 212,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Senior Occupational Therapist'
                    ],
                    [
                        'id' => 213,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Grill Chef de Partie'
                    ],
                    [
                        'id' => 215,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Deputy General Manager'
                    ],
                    [
                        'id' => 216,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Food and Beverage Waiter/ess'
                    ],
                    [
                        'id' => 217,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Assistant Team Leader'
                    ],
                    [
                        'id' => 218,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Assistant Team Leader'
                    ],
                    [
                        'id' => 219,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'book keeper'
                    ],
                    [
                        'id' => 220,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Accuracy Checking Technician'
                    ],
                    [
                        'id' => 222,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pastry Senior Sous Chef'
                    ],
                    [
                        'id' => 223,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Physiologist'
                    ],
                    [
                        'id' => 224,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Housekeeper'
                    ],
                    [
                        'id' => 226,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Psychology'
                    ],
                    [
                        'id' => 227,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Of Care'
                    ],
                    [
                        'id' => 228,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Pastry Chef'
                    ],
                    [
                        'id' => 229,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Maintenance Engineer'
                    ],
                    [
                        'id' => 230,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Machine Operator'
                    ],
                    [
                        'id' => 231,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Third in Charge'
                    ],
                    [
                        'id' => 232,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Lead Occupational Therapist'
                    ],
                    [
                        'id' => 233,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Director of Clinical'
                    ],
                    [
                        'id' => 234,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Admission and Discharge Co-ordinator'
                    ],
                    [
                        'id' => 235,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Human Resource'
                    ],
                    [
                        'id' => 238,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Spa Host'
                    ],
                    [
                        'id' => 239,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Navigator'
                    ],
                    [
                        'id' => 240,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Legal Executive'
                    ],
                    [
                        'id' => 241,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Lawyer'
                    ],
                    [
                        'id' => 242,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Conveyancer'
                    ],
                    [
                        'id' => 243,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Solicitor'
                    ],
                    [
                        'id' => 244,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Practice Manager'
                    ],
                    [
                        'id' => 245,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Deputy Housekeeper'
                    ],
                    [
                        'id' => 246,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Maintenance Manager'
                    ],
                    [
                        'id' => 247,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Hospital Director'
                    ],
                    [
                        'id' => 248,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Gardener'
                    ],
                    [
                        'id' => 249,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Driver'
                    ],
                    [
                        'id' => 250,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Secretary'
                    ],
                    [
                        'id' => 251,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Cluster Sales Exective'
                    ],
                    [
                        'id' => 252,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Trainer'
                    ],
                    [
                        'id' => 253,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Practise Educator'
                    ],
                    [
                        'id' => 254,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Third Incharge'
                    ],
                    [
                        'id' => 255,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Teacher'
                    ],
                    [
                        'id' => 256,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'butcher'
                    ],
                    [
                        'id' => 257,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Practitioner'
                    ],
                    [
                        'id' => 258,
                        'specialist_title' => 'nurse specialist',
                        'specialist_prof' => 'Admin'
                    ],
                    [
                        'id' => 259,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Sales & Marketing Manager'
                    ],
                    [
                        'id' => 260,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Head Receptionist'
                    ],
                    [
                        'id' => 261,
                        'specialist_title' => 'nonnurse specialist',
                        'specialist_prof' => 'Pestry Chef'
                    ]
                ];
                
                $requested_job_title = strtolower($row['applicant_job_title']);
                if($requested_job_title != 'nonnurse specialist' || $requested_job_title != 'nurse specialist' || $requested_job_title != 'non-nurse specialist'){
                    $job_title = JobTitle::whereRaw(
                        "LOWER(REGEXP_REPLACE(name, '[^a-z0-9]', '')) = ?",
                        [preg_replace('/[^a-z0-9]/', '', strtolower($requested_job_title))]
                        )->first();
                        
                    if ($job_title) {
                        $job_category_id = $job_title->job_category_id;
                        $job_title_id = $job_title->id;
                        $job_type = $job_title->type;
                    } else {
                        Log::channel('daily')->warning("Row {$rowIndex}: Job title not found: {$requested_job_title}");
                    }
                }else{
                    foreach ($specialists as $specialist) {
                        if ($specialist['id'] == $row['job_title_prof']) {
                            $job_title = JobTitle::whereRaw('LOWER(name) = ?', [strtolower($specialist['specialist_prof'])])->first();
                            if ($job_title) {
                                $job_title_id = $job_title->id;
                                $job_type = $job_title->type;
                            } else {
                                Log::channel('daily')->warning("Row {$rowIndex}: Job title not found against specialist id: {$requested_job_title}");
                            }
                        }
                    }
                }               

                // Handle job source
                $sourceRaw = $row['applicant_source'] ?? '';
                $cleanedSource = is_string($sourceRaw) ? strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $sourceRaw))) : '';
                $firstTwoWordsSource = implode(' ', array_slice(explode(' ', $cleanedSource), 0, 2));
                $jobSource = JobSource::whereRaw('LOWER(name) = ?', [$firstTwoWordsSource])->first();
                $jobSourceId = $jobSource ? $jobSource->id : 2; // Default to Reed

                // Handle applicant name
                $rawName = $row['applicant_name'] ?? '';
                $cleanedName = is_string($rawName) ? preg_replace('/\s+/', ' ', trim($rawName)) : '';
                preg_match('/\d{10,}/', $cleanedName, $matches);
                $extractedNumber = $matches[0] ?? null;
                $cleanedNumber = $extractedNumber ? preg_replace('/\D/', '', $extractedNumber) : null;
                $finalName = trim(preg_replace('/[^\p{L}\s]/u', '', $cleanedName)) ?: null;

                // Handle phone numbers
                $normalizePhone = function ($input) use ($rowIndex) {
                    if (!is_string($input) || empty($input)) {
                        Log::channel('daily')->debug("Row {$rowIndex}: Empty or invalid phone input");
                        return null;
                    }
                    $digits = preg_replace('/\D/', '', $input);
                    if (strlen($digits) === 11) {
                        return $digits;
                    } elseif (strlen($digits) === 10 && ($digits[0] === '7')) {
                        return '0' . $digits;
                    }
                    Log::channel('daily')->debug("Row {$rowIndex}: Invalid phone format: {$input}");
                    return null;
                };

                $rawPhone = $row['applicant_phone'] ?? '';
                $parts = is_string($rawPhone) ? array_map('trim', explode('/', $rawPhone)) : [];
                $firstRaw = $parts[0] ?? '0';
                $secondRaw = $parts[1] ?? '';
                $input = $normalizePhone($firstRaw);
                if (preg_match('/\b\d{11}\b/', $input, $matches)) {
                    $phone = $matches[0]; 
                } else {
                    $phone = 0;            // If no valid 11-digit number found
                }
                $landlinePhoneInput = $normalizePhone($secondRaw);
                $homePhone = $normalizePhone($landlinePhoneInput);
                if (preg_match('/\b\d{11}\b/', $homePhone, $matches)) {
                    $landlinePhone = $matches[0]; 
                } else {
                    $landlinePhone = null;            // If no valid 11-digit number found
                }

                $rawHomePhoneInput = $row['applicant_homePhone'] ?? '';
                $homePhone = $normalizePhone($rawHomePhoneInput);
                if (preg_match('/\b\d{11}\b/', $homePhone, $matches)) {
                    $rawHomePhone = $matches[0]; 
                } else {
                    $rawHomePhone = null;            // If no valid 11-digit number found
                }
                $applicantLandline = ($rawHomePhone ?? $landlinePhone) ?? $cleanedNumber ?? 0;

                // Handle is_crm_interview_attended
                $is_crm_interview_attended = match (strtolower($row['is_crm_interview_attended'] ?? '')) {
                    'yes' => 1,
                    'pending' => 2,
                    'no' => 0
                };

                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'applicant_uid' => $row['id'] ? md5($row['id']) : null,
                    'user_id' => $row['applicant_user_id'] ?? null,
                    'applicant_name' => $finalName,
                    'applicant_email' => $row['applicant_email'] ?? '-',
                    'applicant_notes' => $row['applicant_notes'] ?? null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'gender' => 'u',
                    'applicant_cv' => $row['applicant_cv'] ?? null,
                    'updated_cv' => $row['updated_cv'] ?? null,
                    'applicant_postcode' => $cleanPostcode,
                    'applicant_experience' => $row['applicant_experience'] ?? null,
                    'job_category_id' => $job_category_id,
                    'job_source_id' => $jobSourceId,
                    'job_title_id' => $job_title_id,
                    'job_type' => $job_type,
                    'applicant_phone' => $phone,
                    'applicant_landline' => $applicantLandline,
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
                    'have_nursing_home_experience' => $row['have_nursing_home_experience'] != 'NULL' ? $row['have_nursing_home_experience'] : null,
                    'paid_status' => $row['paid_status'] ?? null,
                    'paid_timestamp' => $paid_timestamp,
                    'status' => isset($row['status']) && strtolower($row['status']) == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                ];
                $processedData[] = $processedRow;
            }

            // Batch insert/update
            $successfulRows = 0;
            foreach (array_chunk($processedData, 100) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            $rowIndex = $index + 2; // Adjust for header and 1-based indexing
                            try {
                                $applicant = Applicant::updateOrCreate(
                                    ['id' => $row['id']],
                                    array_filter($row, fn($value) => !is_null($value))
                                );
                                Log::channel('daily')->info("Row {$rowIndex}: Applicant created/updated: ID={$applicant->id}");
                                $successfulRows++;
                            } catch (\Exception $e) {
                                Log::channel('daily')->error("Row {$rowIndex}: Failed to save applicant - {$e->getMessage()}");
                                $failedRows[] = ['row' => $rowIndex, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::channel('daily')->error("Failed to process chunk: {$e->getMessage()}");
                    $failedRows[] = ['row' => $rowIndex, 'error' => "Chunk failed: {$e->getMessage()}"];
                }
            }

            // Clean up uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::channel('daily')->info("Deleted temporary file: {$filePath}");
            }

            Log::channel('daily')->info("CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));
            return response()->json([
                'message' => 'CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error("CSV import failed: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::channel('daily')->info("Deleted temporary file after error: {$filePath}");
            }
            return response()->json([
                'error' => 'CSV import failed: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
    public function officesImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,xlsx',
        ]);

        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5G');

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

                $names = array_map('trim', explode(',', $row['office_contact_name'] ?? ''));
                $emails = array_map('trim', explode(',', $row['office_email'] ?? ''));
                $phones = array_map('trim', explode(',', $row['office_contact_phone'] ?? ''));
                $landlines = array_map('trim', explode(',', $row['office_contact_landline'] ?? ''));

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
                    'contacts' => $contacts
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

                    foreach ($row['contacts'] as $contactData) {
                        $office->contact()->create($contactData);
                    }
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
    public function salesImport(Request $request)
    {
        // Validate file (115 MB limit, CSV only)
        $request->validate([
            'csv_file' => 'required|file|mimes:csv', // 115 MB
        ]);

        // Set PHP limits
        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5G');

        // Ensure storage/logs is writable
        $logFile = storage_path('logs/laravel.log');
        if (!is_writable(dirname($logFile))) {
            return response()->json([
                'error' => 'Log directory is not writable. Check permissions for storage/logs.',
            ], 500);
        }

        try {
            // Store file
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            // Check if file was stored
            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Load CSV
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $postcodesToInsert = [];
            $outcodesToInsert = [];
            $successfulRows = 0;
            $rowIndex = 1;

            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false) {
                    Log::warning("Skipped row {$rowIndex} due to header mismatch.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Header mismatch'];
                    continue;
                }

                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                $cleanPostcode = '0';
                if (!empty($row['postcode'])) {
                    preg_match('/[A-Z]{1,2}[0-9]{1,2}\s*[0-9][A-Z]{2}/i', $row['postcode'], $matches);
                    $cleanPostcode = $matches[0] ?? substr(trim($row['postcode']), 0, 8);
                }

                $lat = (is_numeric($row['lat']) ? (float) $row['lat'] : 0.0000);
                $lng = (is_numeric($row['lng']) ? (float) $row['lng'] : 0.0000);

                if ($lat === 0.0000 && $lng === 0.0000) {
                    $postcode_query = strlen($cleanPostcode) < 6
                        ? DB::table('outcodepostcodes')->where('outcode', $cleanPostcode)->first()
                        : DB::table('postcodes')->where('postcode', $cleanPostcode)->first();
                    $lat = $postcode_query ? $postcode_query->lat : 0.0000;
                    $lng = $postcode_query ? $postcode_query->lng : 0.0000;
                }

                if (strlen($cleanPostcode) === 8 && !DB::table('postcodes')->where('postcode', $cleanPostcode)->exists()) {
                    $postcodesToInsert[] = [
                        'postcode' => $cleanPostcode,
                        'lat' => $lat,
                        'lng' => $lng,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } elseif (strlen($cleanPostcode) < 6 && !DB::table('outcodepostcodes')->where('outcode', $cleanPostcode)->exists()) {
                    $outcodesToInsert[] = [
                        'outcode' => $cleanPostcode,
                        'lat' => $lat,
                        'lng' => $lng,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $requested_job_title = $row['job_title'];
                $specialTitles = [
                    'nonnurse specialist',
                    'nurse specialist',
                    'non-nurse specialist',
                    'select job title'
                ];

                if (!in_array(strtolower($requested_job_title), $specialTitles)) {
                    $job_title = JobTitle::whereRaw(
                        "LOWER(REGEXP_REPLACE(name, '[^a-z0-9]', '')) = ?",
                        [preg_replace('/[^a-z0-9]/', '', strtolower($requested_job_title))]
                    )->first();
                    
                    if ($job_title) {
                        $job_category_id = $job_title->job_category_id;
                        $job_title_id = $job_title->id;
                        $job_type = $job_title->type;
                    } else {
                        Log::warning("Row {$rowIndex}: Job title not found first: {$requested_job_title}");
                    }
                } else {
                    $catStr = ($requested_job_title == 'nonnurse specialist' || $requested_job_title == 'non-nurse specialist')
                        ? 'non nurse'
                        : 'nurse';

                    $job_category = JobCategory::whereRaw(
                        "LOWER(REGEXP_REPLACE(name, '[^a-z0-9]', '')) = ?",
                        [preg_replace('/[^a-z0-9]/', '', strtolower($catStr))]
                    )->first();

                    if ($job_category) {
                        $job_title = JobTitle::where('job_category_id', $job_category->id)->first();
                        
                        if ($job_title) {
                            $job_category_id = $job_title->job_category_id;
                            $job_title_id = $job_title->id;
                            $job_type = $job_title->type;
                        } else {
                            Log::warning("Row {$rowIndex}: Job title not found second: {$requested_job_title}");
                        }
                    } else {
                        Log::warning("Row {$rowIndex}: Job category not found for: {$requested_job_title}");
                    }
                }

                $status = match (strtolower($row['status'] ?? '')) {
                    'pending' => 2,
                    'active' => 1,
                    'disable' => 0,
                    'rejected' => 3,
                    default => 0
                };

                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'sale_uid' => $row['id'] ? md5($row['id']) : null,
                    'user_id' => $row['user_id'] ?? null,
                    'office_id' => $row['head_office'] ?? null,
                    'unit_id' => $row['head_office_unit'] ?? null,
                    'sale_postcode' => $cleanPostcode,
                    'job_category_id' => $job_category_id,
                    'job_title_id' => $job_title_id,
                    'job_type' => $job_type,
                    'position_type' => $row['job_type'] ?? null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'cv_limit' => $row['send_cv_limit'] ?? 0,
                    'timing' => $row['timing'] ?? '',
                    'experience' => $row['experience'] ?? '',
                    'salary' => $row['salary'] ?? '',
                    'benefits' => $row['benefits'] ?? '',
                    'qualification' => $row['qualification'] ?? '',
                    'job_description' => $row['job_description'] ?? null,
                    'is_on_hold' => $row['is_on_hold'] ?? 0,
                    'is_re_open' => $row['is_re_open'] ?? 0,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                ];
                $processedData[] = $processedRow;
            }

            // Batch insert postcodes
            if (!empty($postcodesToInsert)) {
                DB::table('postcodes')->insert($postcodesToInsert);
                Log::info('Inserted ' . count($postcodesToInsert) . ' postcodes');
            }
            if (!empty($outcodesToInsert)) {
                DB::table('outcodepostcodes')->insert($outcodesToInsert);
                Log::info('Inserted ' . count($outcodesToInsert) . ' outcodes');
            }

            // Batch save data to database
            $successfulRows = 0;
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                Sale::updateOrCreate(
                                    ['id' => $row['id']],
                                    array_diff_key($row, ['contact' => ''])
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            // Prepare response
            $response = [
                'message' => 'CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function unitsImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,xlsx',
        ]);

        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5G');

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
    public function usersImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,xlsx',
        ]);

        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5G');

        try {
            $file = $request->file('csv_file');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            $data = array_map('str_getcsv', file($filePath));

            if (count($data) < 2) {
                return response()->json(['error' => 'CSV file is empty or invalid.'], 400);
            }

            $headers = array_map('trim', $data[0]);

            for ($i = 1; $i < count($data); $i++) {
                $row = array_combine($headers, $data[$i]);

                if (!$row || !isset($row['name'], $row['email'])) {
                    continue; // Skip incomplete row
                }

                try {
                    $createdAt = !empty($row['created_at']) 
                        ? Carbon::createFromFormat('m/d/Y H:i', $row['created_at'])->format('Y-m-d H:i:s') 
                        : now();

                    $updatedAt = !empty($row['updated_at']) 
                        ? Carbon::createFromFormat('m/d/Y H:i', $row['updated_at'])->format('Y-m-d H:i:s') 
                        : now();
                } catch (\Exception $e) {
                    Log::error('Date format error: ' . $e->getMessage());
                    continue; // Skip row with bad date
                }

                try {
                    User::updateOrInsert(
                        ['id' => $row['id']], // Match by ID
                        [
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => $row['password'],
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt,
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to import user: " . json_encode($row) . ' — ' . $e->getMessage());
                    continue; // Skip row on DB error
                }
            }

            return response()->json(['message' => 'CSV imported and users saved successfully.']);

        } catch (\Exception $e) {
            Log::error('CSV import failed: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the CSV.'], 500);
        }
    }
    public function messagesImport(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);
            
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Messages CSV import');

            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);

                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                try {
                    $date = !empty($row['date'])
                        ? Carbon::parse($row['date'])->format('Y-m-d')
                        : now()->format('Y-m-d');
                    $time = !empty($row['time']) ? Carbon::createFromFormat('H:i:s', $row['time'])->format('H:i:s') : now()->format('H:i:s');
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'msg_id' => $row['msg_id'] ?? null,
                    'module_id' => $row['applicant_id'] ?? null,
                    'module_type' => 'Horsefly\Applicant',
                    'user_id' => $row['user_id'] ?? null,
                    'message' => $row['message'] ?? '',
                    'phone_number' => $row['phone_number'] ?? null,
                    'status' => $row['status'] ?? 0,
                    'is_read' => $row['is_read'] ?? 0,
                    'is_sent' => 1,
                    'date' => $date,
                    'time' => $time,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                Message::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Messages CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Messages CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Messages CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function applicantNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Applicant Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['details'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'note_uid' => $row['id'] ? md5($row['id']) : null,
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'] ?? null,
                    'details' => $row['details'] ?? '',
                    'moved_tab_to' => $row['moved_tab_to'] ?? null,
                    'status' => isset($row['status']) && strtolower($row['status']) === 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                ApplicantNote::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Applicant Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Applicant Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Applicant Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function applicantPivotSaleImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Applicant Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['applicant_id'], $row['applicant_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'],
                    'pivot_uid' => md5($row['id']),
                    'applicant_id' => $row['applicant_id'] ?? null,
                    'sale_id' => $row['sales_id'] ?? null,
                    'is_interested' => $row['is_interested'] == 'yes' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                ApplicantPivotSale::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Applicant Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Applicant Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Applicant Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function notesRangeForPivotSaleImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Applicant Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['applicants_pivot_sales_id'], $row['applicants_pivot_sales_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'],
                    'range_uid' => md5($row['id']),
                    'applicants_pivot_sales_id' => $row['applicants_pivot_sales_id'] ?? null,
                    'reason' => $row['reason'] ?? null,
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                NotesForRangeApplicant::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Pivot Notes Range CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Pivot Notes Range CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Pivot Notes Range CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function auditsImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv',
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Audits CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['auditable_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $rawCreatedAt = trim($row['created_at'] ?? '');
                    $rawUpdatedAt = trim($row['updated_at'] ?? '');

                    $createdAt = (!empty($rawCreatedAt) && strtolower($rawCreatedAt) !== 'null')
                        ? Carbon::parse($rawCreatedAt)->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');

                    $updatedAt = (!empty($rawUpdatedAt) && strtolower($rawUpdatedAt) !== 'null')
                        ? Carbon::parse($rawUpdatedAt)->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');

                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }


                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'user_id' => $row['user_id'] ?? null,
                    'auditable_id' => $row['auditable_id'] ?? null,
                    'auditable_type' => $row['auditable_type'] ?? null,
                    'data' => $row['data'] ?? '',
                    'message' => $row['message'] ?? '',
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                Audit::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Audits CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Audits CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Audits CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function crmNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '5G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Audits CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sales_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'crm_notes_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sales_id'],
                    'details' => $row['details'] ?? '',
                    'moved_tab_to' => $row['moved_tab_to'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                CrmNote::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CRM Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'CRM Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('CRM Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function crmRejectedCvImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting CRM Rejected Cv CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'crm_rejected_cv_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'crm_note_id' => $row['crm_note_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'reason' => $row['reason'] ?? '',
                    'crm_rejected_cv_note' => $row['crm_rejected_cv_note'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                CrmRejectedCv::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CRM Rejected Cv CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'CRM Rejected Cv CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('CRM Rejected Cv CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function cvNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting CV Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'cv_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'details' => $row['details'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                CVNote::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CV Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'CV Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('CV Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function historyImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting CV Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'history_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'stage' => $row['stage'] ?? '',
                    'sub_stage' => $row['sub_stage'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                History::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CV Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'CV Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('CV Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function interviewImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Inteview CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $schedule_date = !empty($row['schedule_date'])
                        ? Carbon::parse($row['schedule_date'])->format('Y-m-d')
                        : now()->format('Y-m-d');
                    $schedule_time = !empty($row['schedule_time']) 
                        ? Carbon::createFromFormat('H:i', $row['schedule_time'])->format('H:i:s') 
                        : now()->format('H:i:s');
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'interview_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'details' => $row['details'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'schedule_date' => $schedule_date,
                    'schedule_time' => $schedule_time,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                Interview::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Interview CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Interview CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Interview CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function ipAddressImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Ip Address CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['ip_address'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'ip_address' => $row['ip_address'],
                    'user_id' => $row['user_id'] ?? null,
                    'mac_address' => $row['mac_address'],
                    'device_type' => $row['device_type'],
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                IPAddress::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("IP Address CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'IP Address CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('IP Address CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function moduleNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Module Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['module_noteable_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'module_note_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'module_noteable_id' => $row['module_noteable_id'],
                    'module_noteable_type' => $row['module_noteable_type'],
                    'details' => $row['details'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                ModuleNote::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Module Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Module Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Module Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function qualityNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Quality Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'quality_notes_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'moved_tab_to' => $row['moved_tab_to'],
                    'details' => $row['details'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                QualityNotes::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Quality Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Quality Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Quality Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function regionsImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Regions CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['districts_code'], $row['districts_code'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'name' => $row['name'] ?? null,
                    'districts_code' => $row['districts_code']
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                Region::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Regions CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Regions CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Regions CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function revertStageImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Revert Stage CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['applicant_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'],
                    'sale_id' => $row['sale_id'],
                    'notes' => $row['notes'] ?? '',
                    'stage' => $row['stage'] ?? '',
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                RevertStage::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("CV Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'CV Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('CV Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function saleDocumentsImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Sale Documents CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['sale_id'], $row['document_path'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'user_id' => $row['user_id'] ?? null,
                    'sale_id' => $row['sale_id'],
                    'document_name' => $row['document_name'],
                    'document_path' => $row['document_path'] ?? '',
                    'document_size' => $row['document_size'] ?? '',
                    'document_extension' => $row['document_extension'] ?? '',
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                SaleDocument::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Sale Documents CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Sale Documents CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Sale Documents CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function saleNotesImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Sale Notes CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['user_id'], $row['sale_id'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'sales_notes_uid' => md5($row['id']),
                    'user_id' => $row['user_id'] ?? null,
                    'sale_id' => $row['sale_id'],
                    'sale_note' => $row['sale_note'] ?? '',
                    'status' => $row['status'] == 'active' ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                SaleNote::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Sale Notes CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Sale Notes CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Sale Notes CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    public function sentEmailDataImport(Request $request)
    {
        try {
            // Validate file (115 MB limit, CSV only)
            $request->validate([
                'csv_file' => 'required|file|mimes:csv'
            ]);

            // Set PHP limits
            ini_set('max_execution_time', 10000);
            ini_set('memory_limit', '1G');

            // Check log directory writability
            $logFile = storage_path('logs/laravel.log');
            if (!is_writable(dirname($logFile))) {
                return response()->json(['error' => 'Log directory is not writable.'], 500);
            }

            Log::info('Starting Sent Email CSV import');

            // Store file with unique timestamped name
            $file = $request->file('csv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/import_files', $filename);
            $filePath = storage_path("app/{$path}");

            if (!file_exists($filePath)) {
                Log::error("Failed to store file at: {$filePath}");
                return response()->json(['error' => 'Failed to store uploaded file.'], 500);
            }
            Log::info('File stored at: ' . $filePath);

            // Stream encoding conversion
            $encoding = $this->detectEncoding($filePath);
            if ($encoding !== 'UTF-8') {
                $tempFile = $filePath . '.utf8';
                $handleIn = fopen($filePath, 'r');
                if ($handleIn === false) {
                    Log::error("Failed to open file for reading: {$filePath}");
                    return response()->json(['error' => 'Failed to read uploaded file.'], 500);
                }
                $handleOut = fopen($tempFile, 'w');
                if ($handleOut === false) {
                    fclose($handleIn);
                    Log::error("Failed to create temporary file: {$tempFile}");
                    return response()->json(['error' => 'Failed to create temporary file.'], 500);
                }
                while (!feof($handleIn)) {
                    $chunk = fread($handleIn, 8192);
                    fwrite($handleOut, mb_convert_encoding($chunk, 'UTF-8', $encoding));
                }
                fclose($handleIn);
                fclose($handleOut);
                unlink($filePath);
                rename($tempFile, $filePath);
                Log::info("Converted CSV to UTF-8 from {$encoding}");
            }

            // Parse CSV with league/csv
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            $headers = $csv->getHeader();
            $records = $csv->getRecords();
            $expectedColumnCount = count($headers);
            Log::info('Headers: ' . json_encode($headers) . ', Count: ' . $expectedColumnCount);

            $processedData = [];
            $failedRows = [];
            $successfulRows = 0;
            $rowIndex = 1;

            // Process CSV rows
            foreach ($records as $row) {
                $rowIndex++;
                if ($rowIndex % 1000 === 0) {
                    Log::info("Processing row {$rowIndex}");
                }

                $row = array_pad($row, $expectedColumnCount, null);
                $row = array_slice($row, 0, $expectedColumnCount);
                $row = array_combine($headers, $row);
                if ($row === false || !isset($row['sent_from'], $row['sent_to'])) {
                    Log::warning("Skipped row {$rowIndex}: Invalid or incomplete data.");
                    $failedRows[] = ['row' => $rowIndex, 'error' => 'Invalid or incomplete data'];
                    continue;
                }

                // Clean string values
                $row = array_map(function ($value) {
                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', ' ', trim($value));
                        $value = preg_replace('/[^\x20-\x7E]/', '', $value);
                    }
                    return $value;
                }, $row);

                // Handle date and time formats
                try {
                    $createdAt = !empty($row['created_at'])
                        ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                    $updatedAt = !empty($row['updated_at'])
                        ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
                        : now()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Row {$rowIndex}: Invalid date format - {$e->getMessage()}");
                    $createdAt = now()->format('Y-m-d H:i:s');
                    $updatedAt = now()->format('Y-m-d H:i:s');
                }

                // Prepare row for insertion
                $processedRow = [
                    'id' => $row['id'] ?? null,
                    'user_id' => $row['user_id'] ?? null,
                    'applicant_id' => $row['applicant_id'] != 'NULL' ? $row['applicant_id'] : null,
                    'sale_id' => $row['sale_id'] != 'NULL' ? $row['sale_id'] : null,
                    'action_name' => $row['action_name'] ?? '',
                    'sent_from' => $row['sent_from'] ?? '',
                    'sent_to' => $row['sent_to'] ?? '',
                    'cc_emails' => $row['cc_emails'] ?? '',
                    'subject' => $row['subject'] ?? '',
                    'title' => $row['title'] ?? '',
                    'template' => $row['template'] ?? '',
                    'status' => $row['status'] == 1 ? 1 : 0,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                $processedData[] = $processedRow;
            }

            // Insert rows in batches
            foreach (array_chunk($processedData, 1000) as $chunk) {
                try {
                    DB::transaction(function () use ($chunk, &$successfulRows, &$failedRows) {
                        foreach ($chunk as $index => $row) {
                            try {
                                SentEmail::updateOrCreate(
                                    ['id' => $row['id']],
                                    $row
                                );
                                $successfulRows++;
                                if (($index + 1) % 1000 === 0) {
                                    Log::info("Processed " . ($index + 1) . " rows in chunk");
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to save row " . ($index + 2) . ": " . $e->getMessage());
                                $failedRows[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Transaction failed for chunk: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("Deleted temporary file: {$filePath}");
            }

            Log::info("Sent Email CSV import completed. Successful: {$successfulRows}, Failed: " . count($failedRows));

            return response()->json([
                'message' => 'Sent Email CSV import completed.',
                'successful_rows' => $successfulRows,
                'failed_rows' => count($failedRows),
                'failed_details' => $failedRows,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => $e->errors()['csv_file'][0]], 422);
        } catch (\Exception $e) {
            Log::error('Sent Email CSV import failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return response()->json(['error' => 'An error occurred while processing the CSV: ' . $e->getMessage()], 500);
        }
    }
    protected function detectEncoding($filePath)
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            Log::error("Failed to open file for encoding detection: {$filePath}");
            throw new \Exception('Unable to open file for encoding detection.');
        }
        $sample = fread($handle, 4096);
        fclose($handle);
        return mb_detect_encoding($sample, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'UTF-8';
    }
    public function applicantsProcessFile(Request $request)
    {
      
        // $request->validate([
        //     'file' => 'required|file|mimes:pdf,doc,docx|max:2048',
        //     'keywords' => 'required|string',
        // ]);

        $file = $request->file('process_file');
        $keywords = explode(',', $request->input('keywords'));
        $keywords = array_map('trim', $keywords);

        // Extract text based on file type
        $text = $this->extractText($file);
        return $text;
        if (!$text) {
            return back()->with('error', 'Unable to extract text from the file.');
        }

        // Search for keywords
        $foundKeywords = $this->searchKeywords($text, $keywords);

        // Save to database
        $document = $this->saveToDatabase($file, $foundKeywords);

        return back()->with('success', 'File processed successfully. Found keywords: ' . implode(', ', $foundKeywords));
    }

    private function extractText($file)
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->store('documents');

        if ($extension === 'pdf') {
            // try {
                return Pdf::getText(Storage::path($path), 'C:\poppler\bin\pdftotext.exe'); // Adjust path if needed
            // } catch (\Exception $e) {
            //     Log::error('PDF text extraction failed: ' . $e->getMessage());
            //     return null;
            // }
        } elseif (in_array($extension, ['doc', 'docx'])) {
            try {
                $phpWord = IOFactory::load(Storage::path($path));
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            // $text .= $element->getText() . ' ';
                        }
                    }
                }
                return $text;
            } catch (\Exception $e) {
                Log::error('DOC text extraction failed: ' . $e->getMessage());
                return null;
            }
        } elseif ($extension === 'csv') {
            try {
                $csv = Reader::createFromPath(Storage::path($path), 'r');
                $csv->setHeaderOffset(0); // Assumes first row is header, adjust if needed
                $text = '';
                foreach ($csv->getRecords() as $record) {
                    $text .= implode(' ', $record) . ' ';
                }
                return $text;
            } catch (\Exception $e) {
                Log::error('CSV text extraction failed: ' . $e->getMessage());
                return null;
            }
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            try {
                $sheets = Excel::toArray([], Storage::path($path));
                $text = '';
                foreach ($sheets as $sheet) {
                    foreach ($sheet as $row) {
                        $text .= implode(' ', array_filter($row, fn($cell) => !is_null($cell))) . ' ';
                    }
                }
                return $text;
            } catch (\Exception $e) {
                Log::error('Excel text extraction failed: ' . $e->getMessage());
                return null;
            }
        }

        return null;
    }

    private function searchKeywords($text, $keywords)
    {
        $keywords = ['skills','qualification','education','name','contact','phone','experience','postcode'];
        $found = [];
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $found[] = $keyword;
            }
        }
        return $found;
    }

    private function saveToDatabase($file, $foundKeywords)
    {
        return Applicant::create([
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
    }
}
