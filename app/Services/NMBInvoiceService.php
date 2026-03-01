<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NMBInvoiceService
{
    protected $apiUrl;
    protected $apiKey;
    protected $merchantCode;

    public function __construct()
    {
        $this->apiUrl = config('services.nmb.api_url');
        $this->apiKey = config('services.nmb.api_key');
        $this->merchantCode = config('services.nmb.merchant_code');
    }

    /**
     * Generate NMB invoice
     */
    public function generateInvoice(array $invoiceData)
    {
        try {
            $payload = [
                'merchant_code' => $this->merchantCode,
                'invoice_number' => $invoiceData['invoice_number'],
                'customer_name' => $invoiceData['customer_name'],
                'customer_email' => $invoiceData['customer_email'] ?? null,
                'customer_phone' => $invoiceData['customer_phone'],
                'amount' => $invoiceData['amount'],
                'currency' => $invoiceData['currency'] ?? 'TZS',
                'description' => $invoiceData['description'],
                'due_date' => $invoiceData['due_date'] ?? now()->addDays(7)->format('Y-m-d'),
                'callback_url' => $invoiceData['callback_url'] ?? route('nmb.callback'),
                'items' => $invoiceData['items'] ?? []
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->apiUrl . '/invoices', $payload);

            if ($response->successful()) {
                Log::info('NMB invoice generated successfully', [
                    'invoice_number' => $invoiceData['invoice_number'],
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('NMB invoice generation failed', [
                'invoice_number' => $invoiceData['invoice_number'],
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            throw new Exception('Failed to generate NMB invoice: ' . $response->body());

        } catch (Exception $e) {
            Log::error('NMB invoice service error', [
                'invoice_number' => $invoiceData['invoice_number'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get invoice status
     */
    public function getInvoiceStatus(string $invoiceReference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get($this->apiUrl . '/invoices/' . $invoiceReference . '/status');

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get invoice status: ' . $response->body());

        } catch (Exception $e) {
            Log::error('NMB invoice status check error', [
                'invoice_reference' => $invoiceReference,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice(string $invoiceReference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/invoices/' . $invoiceReference . '/cancel');

            if ($response->successful()) {
                Log::info('NMB invoice cancelled', [
                    'invoice_reference' => $invoiceReference
                ]);
                return $response->json();
            }

            throw new Exception('Failed to cancel invoice: ' . $response->body());

        } catch (Exception $e) {
            Log::error('NMB invoice cancellation error', [
                'invoice_reference' => $invoiceReference,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validate invoice data
     */
    protected function validateInvoiceData(array $data): void
    {
        $required = ['invoice_number', 'customer_name', 'customer_phone', 'amount', 'description'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Amount must be a positive number');
        }
    }
}
