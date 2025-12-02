<?php
/**
 * ZPPSU Admission System - API Configuration
 * Centralized configuration for SMS API and other external services
 */

class ApiConfig {
    // SMS API Configuration
    const SMS_DEVICE_ID = '68edf6f3bf50e7762d9d4a9d';
    const SMS_API_KEY = '6be5bb4b-de1d-4fad-a2af-c1edca48f263';
    const SMS_BASE_URL = 'https://api.textbee.dev/api/v1/gateway/devices/';
    const SMS_SENDER_NAME = 'ZPPSU ADMISSION';
    
    // API Endpoints
    const SMS_SEND_ENDPOINT = 'send-sms';
    
    // SMS Settings
    const SMS_TIMEOUT = 30;
    const SMS_MAX_RETRIES = 3;
    
    /**
     * Get SMS API URL
     */
    public static function getSmsUrl() {
        return self::SMS_BASE_URL . self::SMS_DEVICE_ID . '/' . self::SMS_SEND_ENDPOINT;
    }
    
    /**
     * Get SMS API Headers
     */
    public static function getSmsHeaders() {
        return [
            'Content-Type: application/json',
            'x-api-key: ' . self::SMS_API_KEY
        ];
    }
    
    /**
     * Get SMS API Configuration Array
     */
    public static function getSmsConfig() {
        return [
            'device_id' => self::SMS_DEVICE_ID,
            'api_key' => self::SMS_API_KEY,
            'base_url' => self::SMS_BASE_URL,
            'send_endpoint' => self::SMS_SEND_ENDPOINT,
            'sender_name' => self::SMS_SENDER_NAME,
            'timeout' => self::SMS_TIMEOUT,
            'max_retries' => self::SMS_MAX_RETRIES
        ];
    }
    
    /**
     * Validate phone number format (Philippine numbers)
     */
    public static function validatePhoneNumber($phone) {
        // Remove any spaces or special characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's already in E.164 format
        if (preg_match('/^\+639\d{9}$/', $phone)) {
            return $phone;
        }
        
        // Convert 09XXXXXXXXX to +639XXXXXXXXX
        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+63' . substr($phone, 1);
        }
        
        // Convert 9XXXXXXXXX to +639XXXXXXXXX
        if (preg_match('/^9\d{9}$/', $phone)) {
            return '+63' . $phone;
        }
        
        return false;
    }
    
    /**
     * Format phone number for display
     */
    public static function formatPhoneForDisplay($phone) {
        $validated = self::validatePhoneNumber($phone);
        if ($validated) {
            // Format as +63 9XX XXX XXXX
            $number = substr($validated, 3); // Remove +63
            return '+63 ' . substr($number, 0, 3) . ' ' . substr($number, 3, 3) . ' ' . substr($number, 6);
        }
        return $phone;
    }
}
?>
