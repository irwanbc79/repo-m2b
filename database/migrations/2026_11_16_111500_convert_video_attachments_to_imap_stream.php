<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'wmv', '3gp', 'webm', 'flv', 'm4v', 'mpeg', 'mpg'];

        $attachments = DB::table('email_attachments')
            ->where(function ($query) use ($videoExtensions) {
                foreach ($videoExtensions as $ext) {
                    $query->orWhere('filename', 'LIKE', '%.' . $ext)
                          ->orWhere('file_path', 'LIKE', '%.' . $ext);
                }
                $query->orWhere('mime_type', 'LIKE', 'video/%');
            })
            ->get();

        foreach ($attachments as $att) {
            $path = $att->file_path;

            if ($path && $path !== '[IMAP_STREAM]') {
                // Hapus file fisik dari disk server jika ada
                try {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Gagal menghapus file video fisik untuk attachment ID {$att->id}: " . $e->getMessage());
                }
            }

            DB::table('email_attachments')
                ->where('id', $att->id)
                ->update([
                    'file_path' => '[IMAP_STREAM]',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible hook: metadata tetap dipertahankan, file video di-stream dari IMAP
    }
};
