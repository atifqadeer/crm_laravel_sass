<?php

namespace App\Http\Controllers;

use App\Traits\Geocode;
use Carbon\Carbon;
use Exception;
use Horsefly\Applicant;
use Horsefly\JobCategory;
use Horsefly\JobSource;
use Horsefly\JobTitle;
use Horsefly\Message;
use Horsefly\ModuleNote;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Public-website "candidate-form" → CRM applicant.
 *
 * POST /api/applicants
 * Auth: Authorization: Bearer <PORTAL_API_TOKEN>  (or X-API-Token header / ?api_token=)
 *
 * Accepts JSON or multipart/form-data with the website form's field names:
 *   fullName, email, phone, experience, visaStatus, postcode, specialism,
 *   nmcRegistered, nmcPin, ukResident, saleUrl, kp_hp (honeypot)
 * CV (required), one of:
 *   - multipart file field "cv"
 *   - JSON "cv_base64" (raw base64 or data URI) + "cv_filename" (e.g. "john_cv.pdf")
 */
class ApplicantApiController extends Controller
{
    use Geocode;

    private const EXPERIENCE_OPTIONS = ['0-1 Year', '1-3 Years', '3-5 Years', '5-10 Years', '10+ Years'];

    private const VISA_OPTIONS = [
        'British Citizen',
        'Indefinite Leave to Remain (ILR)',
        'Other visa with permanent/unrestricted right to work',
        'Visa with restricted right to work',
        'I require visa sponsorship',
    ];

    /**
     * Website specialism → [CRM job category name(s), CRM job title name(s)].
     * Names are matched case-insensitively; the first one found wins.
     */
    private const SPECIALISM_MAP = [
        'Care / Support Worker' => [['Non Nurse'], ['SUPPORT WORKER / CARE ASSISTANT', 'SUPPORT WORKER', 'CARE ASSISTANT']],
        'Chef'                  => [['Chef'], ['CHEF']],
        'Chef Hotel'            => [['Chef'], ['CHEF HOTEL', 'CHEF']],
        'Head Chef'             => [['Chef'], ['HEAD CHEF']],
        'Nurse'                 => [['Nurse'], ['NURSE', 'RGN']],
        'Nursery'               => [['Nursery'], ['NURSERY NURSE', 'NURSERY PRACTITIONER']],
        'Registered Manager'    => [['Non Nurse'], ['REGISTERED MANAGER']],
        'RGN'                   => [['Nurse'], ['RGN']],
        'RMN'                   => [['Nurse'], ['RMN']],
        'RNLD'                  => [['Nurse'], ['RNLD']],
        'Solicitor'             => [['Specialist', 'Speicialist'], ['SOLICITOR']],
        'Senior Chef'           => [['Chef'], ['SENIOR CHEF', 'SENIOR CHEF DE PARTIE', 'CHEF']],
        'Sous Chef'             => [['Chef'], ['SOUS CHEF']],
    ];

    /** Specialisms that must answer the NMC questions. */
    private const NMC_SPECIALISMS = ['Nurse', 'RGN', 'RMN', 'RNLD'];

    public function store(Request $request)
    {
        $this->assertPortalApiToken($request);

        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if (trim((string) $request->input('kp_hp', '')) !== '') {
            return response()->json(['ok' => true, 'message' => 'Application received.'], 200);
        }

        // Normalise inputs before validation.
        $request->merge([
            'fullName'  => trim(preg_replace('/\s+/', ' ', (string) $request->input('fullName'))),
            'email'     => strtolower(trim((string) $request->input('email'))),
            'phone'     => $this->normalizeUkPhone((string) $request->input('phone')),
            'postcode'  => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('postcode')))),
            'nmcPin'    => $request->filled('nmcPin') ? strtoupper(trim((string) $request->input('nmcPin'))) : null,
        ]);

        $needsNmc = in_array($request->input('specialism'), self::NMC_SPECIALISMS, true);

        $validator = Validator::make($request->all(), [
            'fullName'      => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => ['required', 'regex:/^0\d{9,10}$/'],
            'experience'    => ['nullable', Rule::in(self::EXPERIENCE_OPTIONS)],
            'visaStatus'    => ['required', Rule::in(self::VISA_OPTIONS)],
            'postcode'      => ['required', 'string', 'min:2', 'max:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d ]+$/'],
            'specialism'    => ['required', Rule::in(array_keys(self::SPECIALISM_MAP))],
            'nmcRegistered' => [Rule::requiredIf($needsNmc), 'nullable', Rule::in(['Yes', 'No'])],
            'nmcPin'        => [
                Rule::requiredIf($needsNmc && $request->input('nmcRegistered') === 'Yes'),
                'nullable',
                'regex:/^\d{2}[A-Z]\d{4}[A-Z]$/',
            ],
            'ukResident'    => ['required', Rule::in(['Yes', 'No'])],
            'saleUrl'       => 'nullable|string|max:2048',
            'cv'            => 'required_without:cv_base64|nullable|file|mimes:pdf,doc,docx|max:10240',
            'cv_base64'     => 'required_without:cv|nullable|string',
            'cv_filename'   => ['required_with:cv_base64', 'nullable', 'string', 'max:255', 'regex:/\.(pdf|docx?)$/i'],
        ], [
            'phone.regex'           => 'Please enter a valid UK phone number.',
            'postcode.regex'        => 'Please enter a valid UK postcode.',
            'nmcPin.regex'          => 'NMC PIN must be 8 characters, e.g. 12A3456E.',
            'cv.required_without'        => 'Please upload your CV.',
            'cv_base64.required_without' => 'Please upload your CV.',
            'cv_filename.regex'     => 'CV must be a PDF, DOC or DOCX file.',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please fix the errors in the form.', 422, $validator->errors()->toArray());
        }

        // Duplicate check across every email / phone column the CRM uses.
        $email = $request->input('email');
        $phone = $request->input('phone');

        $duplicate = Applicant::withTrashed()
            ->where(function ($q) use ($email, $phone) {
                $q->where('applicant_email', $email)
                    ->orWhere('applicant_email_secondary', $email)
                    ->orWhere('applicant_phone', $phone)
                    ->orWhere('applicant_phone_secondary', $phone)
                    ->orWhere('applicant_landline', $phone);
            })
            ->exists();

        if ($duplicate) {
            return $this->fail('An application with this email or phone number already exists.', 409, [
                'email' => ['An application with this email or phone number already exists.'],
            ]);
        }

        // Resolve CRM category / title / source.
        [$categoryNames, $titleNames] = self::SPECIALISM_MAP[$request->input('specialism')];

        $category = $this->findByNames(JobCategory::query(), $categoryNames);
        if (!$category) {
            Log::error('Applicant API: job category not found for specialism ' . $request->input('specialism'));
            return $this->fail('Selected specialism is not configured in the CRM.', 422, [
                'specialism' => ['Selected specialism is not configured in the CRM.'],
            ]);
        }

        $title = $this->findByNames(JobTitle::query()->where('job_category_id', $category->id), $titleNames)
            ?? $this->findByNames(JobTitle::query(), $titleNames);

        $source = $this->findByNames(JobSource::query(), [config('services.portal.applicant_source', 'Kingsbury Lead')])
            ?? $this->findByNames(JobSource::query(), ['Other Source']);

        $systemUserId = (int) config('services.portal.applicant_user_id', 1);

        // Location from postcode.
        $location = $this->resolvePostcodeLocation($request->input('postcode'));
        if (!$location) {
            return $this->fail('We could not locate that postcode. Please check it and try again.', 422, [
                'postcode' => ['We could not locate that postcode.'],
            ]);
        }

        $notes = $this->buildNotes($request, $title);
        $cvPath = null;

        DB::beginTransaction();

        try {
            $cvPath = $this->storeCv($request);

            $applicant = Applicant::create([
                'user_id'              => $systemUserId,
                'job_source_id'        => $source?->id,
                'job_category_id'      => $category->id,
                'job_title_id'         => $title?->id,
                'job_type'             => $title?->type ?: (strcasecmp($request->input('specialism'), 'Solicitor') === 0 ? 'specialist' : 'regular'),
                'applicant_name'       => $request->input('fullName'),
                'applicant_email'      => $email,
                'applicant_postcode'   => $request->input('postcode'),
                'applicant_phone'      => $phone,
                'applicant_cv'         => $cvPath,
                'applicant_experience' => $request->input('experience'),
                'applicant_notes'      => $notes,
                'lat'                  => $location['lat'],
                'lng'                  => $location['lng'],
            ]);

            $applicant->update(['applicant_uid' => md5($applicant->id)]);

            // Link any earlier SMS threads from this number to the new applicant.
            Message::where('phone_number', $phone)->update([
                'module_id'   => $applicant->id,
                'module_type' => Applicant::class,
            ]);

            $moduleNote = ModuleNote::create([
                'details'              => $notes,
                'module_noteable_id'   => $applicant->id,
                'module_noteable_type' => Applicant::class,
                'user_id'              => $systemUserId,
            ]);
            $moduleNote->update(['module_note_uid' => md5($moduleNote->id)]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Application received.',
                'data'    => [
                    'applicant_uid' => $applicant->applicant_uid,
                    'job_category'  => $category->name,
                    'job_title'     => $title?->name,
                ],
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            if ($cvPath && File::exists(public_path($cvPath))) {
                File::delete(public_path($cvPath));
            }

            Log::error('Applicant API: error creating applicant: ' . $e->getMessage());

            return $this->fail('Something went wrong while saving your application. Please try again.', 500);
        }
    }

    /**
     * Error response for the website form.
     *
     * Sent with HTTP 200 on purpose: many PHP clients (file_get_contents without
     * ignore_errors, curl with CURLOPT_FAILONERROR, Guzzle with http_errors) drop
     * the body of a 4xx/5xx response, so the form never saw these messages.
     * The real status is kept in the "status" field and the X-Status-Code header.
     */
    private function fail(string $message, int $status, array $errors = [])
    {
        return response()->json([
            'ok'      => false,
            'status'  => $status,
            'message' => $message,
            'errors'  => (object) $errors,
        ], 200, ['X-Status-Code' => (string) $status]);
    }

    /**
     * Strip formatting and convert +44 / 0044 / 44 prefixes to a leading 0.
     */
    private function normalizeUkPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0044')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '44') && strlen($digits) >= 11) {
            $digits = '0' . substr($digits, 2);
        } elseif ($digits !== '' && $digits[0] !== '0' && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    private function findByNames($query, array $names)
    {
        foreach ($names as $name) {
            $match = (clone $query)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])
                ->orderByDesc('is_active')
                ->first();

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Same lookup order as ApplicantController::store(): postcodes → outcodes → Google geocode.
     */
    private function resolvePostcodeLocation(string $postcode): ?array
    {
        $compact = strtolower(preg_replace('/\s+/', '', $postcode));

        $row = DB::table('postcodes')
            ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$compact])
            ->first()
            ?? DB::table('outcodepostcodes')
                ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$compact])
                ->first();

        if ($row && !empty($row->lat) && !empty($row->lng)) {
            return ['lat' => $row->lat, 'lng' => $row->lng];
        }

        try {
            $result = $this->geocode($postcode);
            if (!empty($result['lat']) && !empty($result['lng'])) {
                return ['lat' => $result['lat'], 'lng' => $result['lng']];
            }
        } catch (Exception $e) {
            Log::warning('Applicant API: geocode failed for ' . $postcode . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Save the CV to public/uploads/resume/YYYY/MM/DD (same layout as the CRM form).
     */
    private function storeCv(Request $request): string
    {
        $directory = 'uploads/resume/' . now()->format('Y/m/d');
        $destination = public_path($directory);

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true, true);
        }

        if ($request->hasFile('cv')) {
            /** @var UploadedFile $file */
            $file = $request->file('cv');
            $fileName = $this->safeFileName($file->getClientOriginalName(), $file->getClientOriginalExtension());
            $file->move($destination, $fileName);

            return $directory . '/' . $fileName;
        }

        // Base64 (JSON) upload.
        $payload = (string) $request->input('cv_base64');
        if (str_contains($payload, ',') && str_starts_with($payload, 'data:')) {
            $payload = substr($payload, strpos($payload, ',') + 1);
        }

        $binary = base64_decode($payload, true);
        if ($binary === false || $binary === '') {
            throw new Exception('Invalid CV file encoding.');
        }
        if (strlen($binary) > 10 * 1024 * 1024) {
            throw new Exception('CV is larger than 10MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', // some servers report .docx as zip
            'application/octet-stream',
        ];
        if (!in_array($mime, $allowed, true)) {
            throw new Exception('CV must be a PDF, DOC or DOCX file.');
        }

        $original = (string) $request->input('cv_filename');
        $fileName = $this->safeFileName($original, pathinfo($original, PATHINFO_EXTENSION));
        File::put($destination . '/' . $fileName, $binary);

        return $directory . '/' . $fileName;
    }

    private function safeFileName(string $original, string $extension): string
    {
        $name = pathinfo($original, PATHINFO_FILENAME);
        $name = preg_replace('/[^A-Za-z0-9_]+/', '_', trim($name));
        $name = trim($name, '_') ?: 'cv';
        $extension = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $extension)) ?: 'pdf';

        return substr($name, 0, 100) . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 6) . '.' . $extension;
    }

    /**
     * Screening answers have no dedicated columns, so they go into the applicant notes.
     */
    private function buildNotes(Request $request, $title): string
    {
        $parts = [
            'Website application',
            'Specialism: ' . $request->input('specialism') . ($title ? '' : ' (no matching CRM job title)'),
            'Visa: ' . $request->input('visaStatus'),
            'UK resident: ' . $request->input('ukResident'),
        ];

        if ($request->filled('experience')) {
            $parts[] = 'Experience: ' . $request->input('experience');
        }
        if ($request->filled('nmcRegistered')) {
            $parts[] = 'NMC registered: ' . $request->input('nmcRegistered')
                . ($request->filled('nmcPin') ? ' (PIN ' . $request->input('nmcPin') . ')' : '');
        }
        if ($request->filled('saleUrl')) {
            $parts[] = 'Applied from: ' . $request->input('saleUrl');
        }

        return implode(' | ', $parts) . ' --- By: Website Date: ' . Carbon::now()->format('d-m-Y');
    }

    /**
     * Validate shared portal API token (Bearer / X-API-Token / ?api_token=).
     */
    private function assertPortalApiToken(Request $request): void
    {
        $expected = config('services.portal.token');
        $provided = $request->bearerToken()
            ?: $request->header('X-API-Token')
            ?: $request->query('api_token');

        if (empty($expected) || !is_string($provided) || !hash_equals((string) $expected, (string) $provided)) {
            abort(response()->json([
                'ok' => false,
                'message' => 'Unauthorized. Invalid or missing portal API token.',
            ], 401));
        }
    }
}
