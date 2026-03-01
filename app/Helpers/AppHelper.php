<?php
namespace App\Helpers;

use App\Models\Otp;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentIdGenerator;


use App\Mail\MainMailable;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use Imagick;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Illuminate\Support\HtmlString;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

use BaconQrCode\Renderer\Image\Png;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\RendererInterface;
use BaconQrCode\Writer;

use App\Services\SelcomService;


class AppHelper
{
    public $start_date, $end_date;

    public function __construct()
    {
       $this->start_date = "";
       $this->end_date = "";
    }

    public function fileUpload($file, $folder, $file_extension = null)
    {
        $file_extension = $file->getClientOriginalExtension();
        $file_name = mt_rand().'.'.$file_extension;
        $file->storeAs($folder, $file_name, 'public');
        return '/storage/'.$folder.'/' . $file_name;
    }

    public function uploadPhoto($folder, $file)
    {
        $file_extension = $file->getClientOriginalExtension();
        $file_name = mt_rand().'.'.$file_extension;
        $file->move($folder, $file_name);
        return $folder.'/' . $file_name;
    }

    public function apiResponse($success, $msg = '', $data = [], $status = null)
    {
        return response()->json([
            'success' => $success,
            'status' => $status,
            'message' => $msg,
            'data' => $data
        ]);
    }

    public function customFilter($parameters, $tbl_name)
    {
        // create custom filter
        $parameters = array_filter($parameters);
        $keys   =  array_keys($parameters);
        $values =  array_values($parameters);
        $length = count($parameters);

        $conditions = [];

        for ($i=0; $i<$length; $i++ )
        {
            $key = $tbl_name.'.'.$keys[$i];
            $val = $values[$i];

            if($keys[$i] == 'startDate'){
                $this->start_date = $val;
                continue;
            }

            if($keys[$i] == 'endDate'){
                $this->end_date = $val;
                continue;
            }

            if($keys[$i] == 'stream')
                $key = 'ClassHasStreams'.'.'.$keys[$i];

            if($keys[$i] == 'method')
                $key = 'Payments.type';

             if($keys[$i] == 'className')
                $key = 'Classes.name';

            $conditions[$key] =  $val;
        }

        return [$conditions, $this->start_date, $this->end_date];
    }

    public function generateUniqueNumber($length, $table, $column)
    {
        $randomNumber = Str::random($length);

        // Check if the generated number already exists in the specified table and column
        while (DB::table($table)->where($column, $randomNumber)->exists()) {
            $randomNumber = Str::random($length);
        }

        return $randomNumber;
    }

    public function generateStudentId()
    {
        $last_student_id = StudentIdGenerator::where('type', 'Student')->pluck('number')->first();
        $student_id = str_pad((intval($last_student_id) + 1), strlen($last_student_id), '0', STR_PAD_LEFT);
        StudentIdGenerator::where('type', 'Student')->update(['number' => $student_id]);
        return date('Y').'_'.$student_id;
    }

    public function generateStaffId()
    {
        $last_staff_id = Staff::latest()->pluck('id')->first();
        $last_staff_id = explode("-", $last_staff_id)[1];
        $staff_id = str_pad((intval($last_staff_id) + 1), strlen($last_staff_id), '0', STR_PAD_LEFT);
        return date('Y').'-'.$staff_id;
    }

    public function sendNMBInvoice($invoice)
    {
        try {
            $token = $this->getNMBToken();

            if (!$token) {
                return [
                    'success' => false,
                    'description' => 'Failed to get NMB token',
                    'error' => 'Authentication failed'
                ];
            }

            $data = [
                'reference' => 'SAS953' . str_pad($invoice['payment_id'], 4, '0', STR_PAD_LEFT),
                'student_name' => $invoice['student_name'],
                'student_id' => $invoice['student_number'],
                'amount' => $invoice['amount'],
                'type' => $invoice['type'],
                'code' => 10,
                'callback_url' => 'https://britishschool.sc.tz/nmb/callback',
                'allow_partial' => true,
                'token' => $token
            ];

            $response = Http::post('https://api.mpayafrica.co.tz/v2/invoice_submission', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'description' => 'Success',
                    'data' => $response->json()
                ];
            } else {
                \Log::error('NMB Invoice submission failed', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                    'data_sent' => $data
                ]);

                return [
                    'success' => false,
                    'description' => 'Failed to submit invoice',
                    'error' => $response->body(),
                    'status' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            \Log::error('NMB Invoice submission exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_data' => $invoice
            ]);

            return [
                'success' => false,
                'description' => 'Exception occurred',
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateNMBInvoice($invoice)
    {
        $data = [
            'reference' => 'SAS953' . str_pad($invoice['payment_id'], 4, '0', STR_PAD_LEFT),
            'student_name' => $invoice['student_name'],
            'student_id' => $invoice['student_id'],
            'amount' => $invoice['amount'],
            'type' => $invoice['type'],
            'code' => 10,
            'callback_url' => 'https://britishschool.sc.tz/nmb/callback',
            'allow_partial' => true,
            'token' => $this->getNMBToken()
        ];

        return $response = Http::post('https://api.mpayafrica.co.tz/v2/invoice_update', $data);
    }

    public function getNMBToken()
    {
        try {
            $response = Http::post('https://api.mpayafrica.co.tz/v2/auth', [
                'username' => '545M23SVN8YT0X',
                'password' => '0tmfjjzgpsa%zXUnM1mAr&*%2E98JM786f1',
            ]);

            // Check if response is successful
            if ($response->successful() && isset($response['token'])) {
                return $response['token'];
            }

            // Log error for debugging
            \Log::error('NMB Token generation failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            \Log::error('NMB Token exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function sendSMS($sms)
    {
        $url = env('BEEM_SMS_BASE_URL');
        $api_key = env('BEEM_SMS_API_KEY');
        $secret_key = env('BEEM_SMS_SECRET_KEY');

        $postData = [
            'encoding' => 0,
            'source_addr' => env('BEEM_SMS_SOURCE_ADDR'),
            'message' => $sms['message'],
            'recipients' => [
                array(
                    'recipient_id' => intval(str_replace('_', '', $sms['recipient_id'])),
                    'dest_addr' => '255' . substr($sms['phone'], 1)
                )
            ]
        ];

        return Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$api_key:$secret_key"),
            'Content-Type' => 'application/json',
        ])->post($url, $postData);
    }

    private function processSelcomPay($data)
    {

        // $invoice_number = 'REG-' . $student->id . '-' . date('Y');

        // $paymentData = [
        //     'student_number' => $data['student_number'],
        //     'fee_structure_id' => $feeStructure->id,
        //     'invoice_number' => $invoice_number,
        //     'created_by' => auth('sanctum')->user()->id,
        // ];

        // $payment = Payment::create($paymentData);

        return $selcom->initiateStkPush(
            $data['invoice_number'],
            $data['phone'],
            $data['amount']
        );
    }

    public static function instance()
    {
        return new AppHelper();
    }

}
