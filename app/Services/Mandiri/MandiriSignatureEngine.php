<?php

namespace App\Services\Mandiri;

use Exception;
use Illuminate\Support\Facades\Log;

class MandiriSignatureEngine
{
    /**
     * 1. Generate Asymmetric Signature (RSA-SHA256) untuk Request Access Token B2B.
     * Formula: Base64(SHA256withRSA(Client_ID + "|" + Timestamp))
     *
     * @param string $clientId
     * @param string $timestamp Format ISO 8601 (mis. 2026-07-27T17:08:49+07:00)
     * @param string $privateKeyPem File path / PEM String / Base64 dari RSA Private Key
     * @return string Base64 Signature
     * @throws Exception
     */
    public static function generateAuthSignature(string $clientId, string $timestamp, string $privateKeyPem): string
    {
        $stringToSign = $clientId . '|' . $timestamp;

        // Normalisasi format Private Key jika dikirim via Base64 atau file path
        $formattedKey = static::normalizePrivateKey($privateKeyPem);

        $privateKey = openssl_pkey_get_private($formattedKey);
        if (!$privateKey) {
            throw new Exception('Gagal membaca RSA Private Key untuk API Bank Mandiri.');
        }

        $success = openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new Exception('Gagal membuat RSA-SHA256 Signature untuk API Mandiri.');
        }

        return base64_encode($binarySignature);
    }

    /**
     * 2. Generate Symmetric Signature (HMAC-SHA512) untuk Service Data Requests.
     * Formula: Base64(HMAC_SHA512(Client_Secret, StringToSign))
     * StringToSign = HTTPMethod + ":" + EndpointUrl + ":" + AccessToken + ":" + HexEncode(SHA256(Minify(RequestBody))) + ":" + Timestamp
     *
     * @param string $httpMethod (GET, POST, dll)
     * @param string $relativeEndpoint Path relatif + Query String (mis. /openapi/v1.0/bank-statement)
     * @param string $accessToken Access Token B2B aktif
     * @param array|string $requestBody Content payload
     * @param string $timestamp ISO 8601 timestamp
     * @param string $clientSecret Client Secret Partner
     * @return string Base64 Signature
     */
    public static function generateServiceSignature(
        string $httpMethod,
        string $relativeEndpoint,
        string $accessToken,
        array|string $requestBody,
        string $timestamp,
        string $clientSecret
    ): string {
        // Prepare Minified JSON & Hash
        $minifiedBody = is_array($requestBody)
            ? json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string) $requestBody;

        // SHA-256 Hex Encode dari Minified Body
        $hashedBody = strtolower(hash('sha256', $minifiedBody));

        // Format StringToSign sesuai spesifikasi SNAP BI Mandiri
        $stringToSign = sprintf(
            '%s:%s:%s:%s:%s',
            strtoupper(trim($httpMethod)),
            trim($relativeEndpoint),
            trim($accessToken),
            $hashedBody,
            trim($timestamp)
        );

        $binaryHmac = hash_hmac('sha512', $stringToSign, $clientSecret, true);

        return base64_encode($binaryHmac);
    }

    /**
     * Helper untuk memuat dan merapikan string PEM Private Key
     */
    protected static function normalizePrivateKey(string $keyInput): string
    {
        $keyInput = trim($keyInput);

        // Jika input berupa file path yang valid
        if (file_exists($keyInput)) {
            return file_get_contents($keyInput);
        }

        // Jika input di-encode Base64
        if (!str_contains($keyInput, '-----BEGIN')) {
            $decoded = base64_decode($keyInput, true);
            if ($decoded && str_contains($decoded, '-----BEGIN')) {
                return $decoded;
            }
        }

        return $keyInput;
    }
}
