<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SMSService
{
    protected $apiUrl;
    protected $apiKey;
    protected $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url');
        $this->apiKey = config('services.sms.api_key');
        $this->senderId = config('services.sms.sender_id');
    }

    /**
     * Send SMS
     */
    public function sendSMS(string $phoneNumber, string $message, array $options = [])
    {
        try {
            $payload = [
                'api_key' => $this->apiKey,
                'sender_id' => $this->senderId,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'message' => $message,
                'schedule_time' => $options['schedule_time'] ?? null,
                'callback_url' => $options['callback_url'] ?? route('sms.callback')
            ];

            $response = Http::post($this->apiUrl . '/send', $payload);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $this->maskPhoneNumber($phoneNumber),
                    'message_length' => strlen($message),
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('SMS sending failed', [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            throw new Exception('Failed to send SMS: ' . $response->body());

        } catch (Exception $e) {
            Log::error('SMS service error', [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSMS(array $phoneNumbers, string $message, array $options = [])
    {
        try {
            $results = [];
            $batchSize = $options['batch_size'] ?? 100;

            foreach (array_chunk($phoneNumbers, $batchSize) as $batch) {
                $payload = [
                    'api_key' => $this->apiKey,
                    'sender_id' => $this->senderId,
                    'to' => implode(',', array_map([$this, 'formatPhoneNumber'], $batch)),
                    'message' => $message,
                    'callback_url' => $options['callback_url'] ?? route('sms.callback')
                ];

                $response = Http::post($this->apiUrl . '/bulk', $payload);

                if ($response->successful()) {
                    $results[] = $response->json();
                    Log::info('Bulk SMS batch sent', [
                        'batch_size' => count($batch),
                        'response' => $response->json()
                    ]);
                } else {
                    Log::error('Bulk SMS batch failed', [
                        'batch_size' => count($batch),
                        'response' => $response->json()
                    ]);
                    throw new Exception('Failed to send bulk SMS: ' . $response->body());
                }

                // Rate limiting - wait between batches
                if (count($phoneNumbers) > $batchSize) {
                    usleep($options['batch_delay'] ?? 1000000); // 1 second default
                }
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Bulk SMS service error', [
                'total_numbers' => count($phoneNumbers),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get SMS delivery status
     */
    public function getDeliveryStatus(string $messageId)
    {
        try {
            $response = Http::get($this->apiUrl . '/status/' . $messageId, [
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get SMS status: ' . $response->body());

        } catch (Exception $e) {
            Log::error('SMS status check error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get SMS balance
     */
    public function getBalance()
    {
        try {
            $response = Http::get($this->apiUrl . '/balance', [
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get SMS balance: ' . $response->body());

        } catch (Exception $e) {
            Log::error('SMS balance check error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send welcome SMS to new student
     */
    public function sendWelcomeSMS($student, $password = null)
    {
        $message = "Welcome {$student->first_name} {$student->last_name}! " .
                  "Your registration at British School is complete. " .
                  ($password ? "Your temporary password: {$password}. " : "") .
                  "For assistance, call our office.";

        return $this->sendSMS($student->phone, $message);
    }

    /**
     * Send fee payment reminder
     */
    public function sendFeeReminder($student, $amount, $dueDate)
    {
        $message = "Dear {$student->first_name}, this is a reminder that your fee " .
                  "payment of TZS {$amount} is due by {$dueDate}. " .
                  "Please make payment to avoid late fees. Thank you.";

        return $this->sendSMS($student->phone, $message);
    }

    /**
     * Send exam results notification
     */
    public function sendExamResults($student, $examName, $results)
    {
        $message = "Dear {$student->first_name}, your {$examName} results are ready. " .
                  "Average score: {$results['average']}. " .
                  "Please visit the school for detailed results.";

        return $this->sendSMS($student->phone, $message);
    }

    /**
     * Send attendance notification
     */
    public function sendAttendanceNotification($student, $date, $status)
    {
        $message = "Attendance alert: {$student->first_name} was marked " .
                  "{$status} on {$date}. Please contact the school if this is incorrect.";

        return $this->sendSMS($student->phone, $message);
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if missing (Tanzania: +255)
        if (strlen($phone) === 9 && in_array(substr($phone, 0, 1), ['6', '7'])) {
            return '255' . $phone;
        }

        if (strlen($phone) === 12 && substr($phone, 0, 3) === '255') {
            return $phone;
        }

        // If it starts with 0, replace with country code
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '255' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $formatted = $this->formatPhoneNumber($phone);
        if (strlen($formatted) >= 6) {
            return substr($formatted, 0, 3) . '***' . substr($formatted, -3);
        }
        return '***';
    }

    /**
     * Validate phone number
     */
    protected function validatePhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);
        return preg_match('/^255[67]\d{8}$/', $formatted);
    }

    /**
     * Split long message into chunks
     */
    protected function splitMessage(string $message, int $maxLength = 160): array
    {
        if (strlen($message) <= $maxLength) {
            return [$message];
        }

        $chunks = [];
        $words = explode(' ', $message);
        $currentChunk = '';

        foreach ($words as $word) {
            if (strlen($currentChunk . ' ' . $word) <= $maxLength) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $word;
            } else {
                if ($currentChunk) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $word;
            }
        }

        if ($currentChunk) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }
}
