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
            $rawBody = trim((string) $email->body);
            if ($rawBody === '') {
                $html = '<p style="font-family:system-ui,sans-serif;color:#9ca3af;padding:1.5rem">'
                    . '(Konten email kosong)</p>';
            } else {
                // Cek apakah isi email berupa HTML atau plain text
                $hasHtmlTags = preg_match("/<[a-z][\s\S]*>/i", $rawBody);
                
                if (!$hasHtmlTags) {
                    // Plain text: bungkus dengan pre-wrap dan styling modern
                    $html = '<div style="white-space: pre-wrap; font-family: system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 14px; line-height: 1.6; color: #374151; padding: 1.5rem; word-break: break-word;">' 
                        . e($rawBody) 
                        . '</div>';
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
                    
                    if (str_contains(strtolower($rawBody), '</head>')) {
                        $html = str_ireplace('</head>', $styleBlock . '</head>', $rawBody);
                    } else {
                        $html = $styleBlock . $rawBody;
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
