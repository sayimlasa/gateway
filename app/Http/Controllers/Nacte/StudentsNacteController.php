<?php

namespace App\Http\Controllers\Nacte;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
//use Illuminate\Http\Client\Response;
class StudentsNacteController extends Controller
{
    public function bachelorProgramme(Request $request)
    {
        $campusId = (int) $request->campus_id;

        if (! in_array($campusId, [1, 2], true)) {
            return response()->json([
                'message' => 'Invalid campus_id'
            ], 422);
        }

        // Get payload from .env via config
        $payload = config("nacte.campuses.$campusId");
        $cacheKey = 'nacte_bachelor_programme_campus_' . $campusId;
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($payload, $campusId) {
            $response = Http::acceptJson()->post(
                'https://www.nacte.go.tz/nacteapi/index.php/bachelor-programme/detailsprogramme',
                $payload
            );

            if (! $response) {
                throw new \Exception('NACTE API request failed');
            }

            // Decode response safely
            $body = is_string($response) ? $response : (string) $response;
            $apiData = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON returned from NACTE API');
            }

            // Prepare final response: top-level campus_id + params
            $data = [
                'campus_id' => $campusId,
                'params' => []
            ];

            if (isset($apiData['params']) && is_array($apiData['params'])) {
                // Add campus_id to each programme
                foreach ($apiData['params'] as &$item) {
                    if (is_array($item)) {
                        $item['campus_id'] = $campusId;
                    }
                }
                unset($item);

                $data['params'] = $apiData['params'];
            }

            return $data;
        });
    }
    public function bachelorEnrollment(Request $request)
    {
        $campusId = (int) $request->campus_id;
        // Validate request
        $request->validate([
            'campus_id' => 'required|integer|in:1,2',
            'academicYear' => 'required|string',
            'programmeId' => 'required|string',
            'programmeCode' => 'required|string',
            'programmeCategory' => 'required|string',
            'level' => 'required|integer',
            'students' => 'required|array|min:1', // force at least one student
            'students.*.particulars.firstName' => 'required|string',
            'students.*.particulars.middleName' => 'nullable|string',
            'students.*.particulars.surname' => 'required|string',
            'students.*.particulars.formIndexNumber' => 'required|string',
            'students.*.particulars.otherFormIndexNumber' => 'nullable|string',
            'students.*.particulars.sex' => 'required|in:M,F',
            'students.*.particulars.email' => 'required|email',
            'students.*.particulars.mobileNumber' => 'required|string',
            'students.*.particulars.address' => 'required|string',
            'students.*.particulars.region' => 'required|string',
            'students.*.particulars.district' => 'required|string',
            'students.*.particulars.dob' => 'required|date_format:d-m-Y',
            'students.*.particulars.nationality' => 'required|string',
            'students.*.particulars.registrationNumber' => 'required|string',
            'students.*.particulars.semester' => 'required|integer',
            'students.*.particulars.deliveringMode' => 'required|integer',
            'students.*.particulars.isYearRepeat' => 'required|integer',
            'students.*.particulars.entryMode' => 'required|integer',
            'students.*.particulars.formSixIndexNumber' => 'nullable|string',
            'students.*.particulars.otherFormSixIndexNumber' => 'nullable|string',
            'students.*.particulars.avn' => 'nullable|string',
            'students.*.particulars.diplomaRegistrationNumber' => 'nullable|string',
            'students.*.particulars.sponsorship' => 'required|integer',
            'students.*.particulars.imparement' => 'required|integer',
            'students.*.particulars.nextKinName' => 'required|string',
            'students.*.particulars.nextKinAddress' => 'required|string',
            'students.*.particulars.nextKinPhoneNumber' => 'required|string',
            'students.*.particulars.nextKinRegion' => 'required|string',
            'students.*.particulars.nextKinEmail' => 'required|email',
            'students.*.particulars.nextKinRelationship' => 'required|string',
        ]);

        // Get credentials from config
        $credentials = config("nacte.campuses.$campusId");
        // Build payload
        $payload = [
            'heading' => [
                'authorization' => $credentials['authorization'],
                'academicYear' => $request->academicYear,
                'programmeId' => $request->programmeId,
                'programmeCode' => $request->programmeCode,
                'programmeCategory' => $request->programmeCategory,
                'level' => $request->level,
                'api_code' => $credentials['api_code_submit'],
            ],
            'students' => $request->students,
        ];

        // Cache key per campus+programme
        $cacheKey = 'nacte_enrollment_campus_' . $campusId . '_programme_' . $request->programmeId;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($payload) {

            $response = Http::acceptJson()->post(
                'https://www.nacte.go.tz/nacteapi/index.php/bachelor-programme/enrollment',
                $payload
            );

            if (! $response) {
                throw new \Exception('NACTE Enrollment API request failed');
            }

            $body = is_string($response) ? $response : (string) $response;
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON returned from NACTE Enrollment API');
            }

            return $data;
        });
    }



    public function studentProcessed(Request $request)
    {
        // 1️⃣ Validate first
        $validated = $request->validate([
            'campus_id'    => 'required|integer|in:1,2',
            'programmeId'  => 'required|string',
            'academicYear' => 'required|string',
        ]);
        $campusId     = (int) $validated['campus_id'];
        $programmeId  = $validated['programmeId'];
        $academicYear = $validated['academicYear'];
        // 2️⃣ Load credentials safely
        $credentials = config("nacte.campuses.$campusId");

        if (!$credentials) {
            return response()->json([
                'message' => 'Invalid campus configuration'
            ], 500);
        }
        // 3️⃣ Dynamic cache key (VERY important)
        $cacheKey = "nacte_student_processed_{$campusId}_{$programmeId}_{$academicYear}";
        // 4️⃣ Cache with Redis
        $data = Cache::remember($cacheKey, now()->addHours(6), function () use (
            $credentials,
            $programmeId,
            $academicYear
        ) {

            $payload = [
                "authorization" => $credentials['authorization'],
                "programmeId"   => $programmeId,
                "academicYear"  => $academicYear,
                "api_code"      => $credentials['api_code_processed'],
            ];
            //die($payload);
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://www.nacte.go.tz/nacteapi/index.php/bachelor-programme/studentprocessed',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]);
            $response = curl_exec($curl);
            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);

                return [
                    'success' => false,
                    'error'   => $error,
                ];
            }
            curl_close($curl);

            return json_decode($response, true);
        });
        return response()->json($data);
    }
    public function studentUnprocessed(Request $request)
    {
        // 1️⃣ Validate first
        $validated = $request->validate([
            'campus_id'    => 'required|integer|in:1,2',
            'programmeId'  => 'required|string',
            'academicYear' => 'required|string',
        ]);
        $campusId     = (int) $validated['campus_id'];
        $programmeId  = $validated['programmeId'];
        $academicYear = $validated['academicYear'];
        // 2️⃣ Load credentials safely
        $credentials = config("nacte.campuses.$campusId");

        if (!$credentials) {
            return response()->json([
                'message' => 'Invalid campus configuration'
            ], 500);
        }
        // 3️⃣ Dynamic cache key (VERY important)
        $cacheKey = "nacte_student_processed_{$campusId}_{$programmeId}_{$academicYear}";
        // 4️⃣ Cache with Redis
        $data = Cache::remember($cacheKey, now()->addHours(6), function () use (
            $credentials,
            $programmeId,
            $academicYear
        ) {
            $payload = [
                "authorization" => $credentials['authorization'],
                "programmeId"   => $programmeId,
                "academicYear"  => $academicYear,
                "api_code"      => $credentials['api_code_unprocessed'],
            ];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://www.nacte.go.tz/nacteapi/index.php/bachelor-programme/studentunprocessed',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]);
            $response = curl_exec($curl);
            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);

                return [
                    'success' => false,
                    'error'   => $error,
                ];
            }
            curl_close($curl);
            return json_decode($response, true);
        });
        return response()->json($data);
    }

    public function getInfrastructure(Request $request)
    {
        // ✅ 1. Validate input properly
        $validated = $request->validate([
            'campus_id' => 'required|integer|in:1,2',
        ]);

        $campusId = (int) $validated['campus_id'];

        // ✅ 2. Load credentials safely
        $credentials = config("nacte.campuses.$campusId");

        if (empty($credentials)) {
            return response()->json([
                'message' => 'NACTE credentials not configured for this campus',
            ], 500);
        }

        $payload = [
            'authorization' => $credentials['authorization'],
            'api_code'      => $credentials['api_code_structure'],
        ];

        // ✅ 3. Strong, unique cache key
        $cacheKey = "nacte_infrastructure_campus_{$campusId}";
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($payload, $campusId) {

            // ✅ 4. HTTP call with timeout + retries
            $response = Http::acceptJson()
                ->timeout(30)
                ->retry(3, 2000)
                ->post(
                    'https://www.nacte.go.tz/nacteapi/index.php/bachelor-programme/infrastructure',
                    $payload
                );
            Log::info('NACTE Response', $response->json());
            // ✅ 5. Handle HTTP errors cleanly
            if ($response->failed()) {
                throw new \RuntimeException(
                    'NACTE API error: ' . $response->status()
                );
            }

            // ✅ 6. Decode JSON safely
            $apiData = $response->json();

            if (! is_array($apiData)) {
                throw new \RuntimeException('Invalid JSON returned from NACTE API');
            }

            //✅ 7. Normalize response structure
            $data = [
                'campus_id' => $campusId,
                'params'    => [],
            ];

            if (! empty($apiData['params']) && is_array($apiData['params'])) {
                $data['params'] = collect($apiData['params'])->map(function ($item) use ($campusId) {
                    if (is_array($item)) {
                        $item['campus_id'] = $campusId;
                    }
                    return $item;
                })->values();
            }

            return $data;
        });
    }
    
}
