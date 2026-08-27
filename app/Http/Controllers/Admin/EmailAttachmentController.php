<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmailAttachmentController extends Controller
{
    /**
     * Download or preview attachment by mailbox & id.
     * Files are stored in: storage/app/public/email_attachments/{mailbox}/{email_id}/filename
     */
    public function download(string $mailbox, string $id, Request $request)
    {
        // Get attachment from database
        $attachment = DB::table('email_attachments')->where('id', $id)->first();
        
        if (!$attachment) {
            abort(404, 'Attachment not found in database');
        }
        
        // Build the correct path (files are in public storage)
        $filePath = 'public/' . $attachment->file_path;
        
        if (!Storage::exists($filePath)) {
            // Try alternative path without 'public/' prefix
            $filePath = $attachment->file_path;
            if (!Storage::exists($filePath)) {
                abort(404, 'File not found: ' . $attachment->file_path);
            }
        }
        
        $fullPath = Storage::path($filePath);
        $mimeType = $attachment->mime_type ?? Storage::mimeType($filePath) ?? 'application/octet-stream';
        $filename = $attachment->filename ?? basename($attachment->file_path);
        
        // Check if inline preview requested
        $mode = $request->query('mode', 'attachment');
        
        if ($mode === 'inline') {
            // For inline preview (PDF, images)
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }
        
        // Default: download
        return response()->download($fullPath, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * Bersihkan header teknis MIME/RFC 822 (Delivered-To, DKIM, Message-ID, dll)
     * dan decode payload quoted-printable/base64 bila ada.
     */
    public static function cleanEmailBody(?string $rawBody): string
    {
        if ($rawBody === null) {
            return '';
        }

        $body = trim($rawBody);
        if ($body === '') {
            return '';
        }

        // Loop untuk menangani multiple header blocks (misal Received/DKIM/MIME beruntun)
        while (preg_match('/^(Delivered-To|DKIM-Signature|Received|Return-Path|From|To|Subject|Message-ID|MIME-Version|Content-Type|Content-Transfer-Encoding|X-Mailer|X-Priority):/im', $body)) {
            $parts = preg_split("/\r?\n\r?\n/", $body, 2);
            if (count($parts) < 2) {
                break;
            }

            $headerPart = $parts[0];
            $candidateBody = $parts[1];

            // Cek encoding pada header
            if (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $headerPart)) {
                $candidateBody = quoted_printable_decode($candidateBody);
            } elseif (preg_match('/Content-Transfer-Encoding:\s*base64/i', $headerPart)) {
                $decoded = base64_decode($candidateBody, true);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $candidateBody = $decoded;
                }
            }

            $body = trim($candidateBody);
        }

        // Jika masih ada boundary multipart MIME yang belum terpecah, ambil isi part-nya
        if (preg_match('/^--([^\r\n]+)/m', $body, $bMatches)) {
            $boundary = preg_quote($bMatches[1], '/');
            $sections = preg_split('/--' . $boundary . '(--)?/', $body);
            foreach ($sections as $sec) {
                $sec = trim($sec);
                if (empty($sec)) continue;
                // Jika part memiliki header sendiri (Content-Type: text/html atau text/plain)
                if (preg_match('/Content-Type:\s*text\/(html|plain)/i', $sec)) {
                    $subParts = preg_split("/\r?\n\r?\n/", $sec, 2);
                    if (isset($subParts[1])) {
                        $partBody = $subParts[1];
                        if (preg_match('/Content-Transfer-Encoding:\s*quoted-printable/i', $subParts[0])) {
                            $partBody = quoted_printable_decode($partBody);
                        } elseif (preg_match('/Content-Transfer-Encoding:\s*base64/i', $subParts[0])) {
                            $decoded = base64_decode($partBody, true);
                            if ($decoded !== false) $partBody = $decoded;
                        }
                        return trim($partBody);
                    }
                }
            }
        }

        return $body;
    }

    /**
     * Show email body (HTML) for iframe.
     * Bypasses Livewire JSON payload limit/WAF rules.
     */
    public function showBody(string $id)
    {
        $email = DB::table('emails')->where('id', $id)->first();

        // PENTING: jangan abort(404) di sini.
        // Endpoint ini dipanggil dari dalam <iframe> pada halaman inbox,
        // sehingga abort(404) akan menampilkan halaman error 404 (skater)
        // di dalam konten inbox. Selalu balas 200 dengan pesan yang ramah.
        if (!$email) {
            $html = '<div style="font-family:system-ui,-apple-system,sans-serif;color:#6b7280;'
                . 'padding:2.5rem;text-align:center">'
                . '<p style="font-size:14px;font-weight:600;color:#374151;margin:0 0 .35rem">'
                . 'Konten email tidak tersedia</p>'
                . '<p style="font-size:12px;margin:0">Email mungkin belum tersinkron. '
                . 'Klik <b>Sync Now</b> lalu buka kembali email ini.</p></div>';
        } else {
            $cleanedBody = self::cleanEmailBody((string) $email->body);

            if ($cleanedBody === '') {
                $html = '<p style="font-family:system-ui,sans-serif;color:#9ca3af;padding:1.5rem">'
                    . '(Konten email kosong)</p>';
            } else {
                // Cek apakah isi email berupa HTML atau plain text
                $hasHtmlTags = preg_match("/<[a-z][\s\S]*>/i", $cleanedBody);
                
                if (!$hasHtmlTags) {
                    // Plain text: bungkus dengan styling bersih, modern, dan nyaman di mata
                    $html = '<!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <style>
                            body {
                                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #1e293b;
                                background-color: #ffffff;
                                padding: 24px;
                                margin: 0;
                                word-break: break-word;
                            }
                            .plain-email-content {
                                white-space: pre-wrap;
                                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                                font-size: 13.5px;
                                line-height: 1.75;
                                color: #1e293b;
                                letter-spacing: 0.01em;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="plain-email-content">' . e($cleanedBody) . '</div>
                    </body>
                    </html>';
                } else {
                    // HTML: Inject CSS global untuk menormalkan tampilan
                    $styleBlock = '<style>
                        body {
                            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                            font-size: 14px;
                            line-height: 1.6;
                            color: #374151;
                            padding: 1.5rem;
                            margin: 0;
                            word-break: break-word;
                        }
                        img {
                            max-width: 100% !important;
                            height: auto !important;
                        }
                        table {
                            max-width: 100% !important;
                            width: 100% !important;
                            border-collapse: collapse !important;
                        }
                    </style>';
                    
                    if (str_contains(strtolower($cleanedBody), '</head>')) {
                        $html = str_ireplace('</head>', $styleBlock . '</head>', $cleanedBody);
                    } else {
                        $html = $styleBlock . $cleanedBody;
                    }
                }
            }
        }

        // Header anti-cache: cegah LiteSpeed menyajikan body/404 basi dari cache.
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-LiteSpeed-Cache-Control', 'no-cache');
    }
}
