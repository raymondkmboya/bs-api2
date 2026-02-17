<?php

namespace App\Services;

use Selcom\ApigwClient\Client;

class SelcomService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            env('SELCOM_BASE_URL'),
            env('SELCOM_API_KEY'),
            env('SELCOM_API_SECRET')
        );
    }

    public function formatTanzanianNumber($phone)
    {
        // 1. Remove all non-numeric characters (spaces, +, dashes)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 2. If it starts with 0, replace it with 255
        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        // 3. Validation: Tanzanian numbers must be exactly 12 digits after formatting
        if (strlen($phone) !== 12 || !str_starts_with($phone, '255')) {
            throw new InvalidArgumentException("Invalid Tanzanian phone number format.");
        }

        return $phone;
    }

    public function initiateStkPush($orderId, $phone, $amount)
    {
        // Selcom 'Minimal Checkout' path for STK Push
        $path = "/checkout/create-order-minimal";

        $formattedPhone = $this->formatTanzanianNumber($phone);

        $orderData = [
            "vendor" => env('SELCOM_VENDOR_ID'),
            "order_id" => $orderId,
            "buyer_email" => "payments@britishschool.ac.tz",
            "buyer_name" => "School Parent",
            "buyer_phone" => $formattedPhone, // Format: 255...
            "amount" => (int) $amount,
            "currency" => "TZS",
            "no_of_items" => 1
        ];

        // The official SDK handles all signing/header logic internally
        return $this->client->postFunc($path, $orderData);
    }
}

?>
