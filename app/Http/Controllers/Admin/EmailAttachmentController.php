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
}
