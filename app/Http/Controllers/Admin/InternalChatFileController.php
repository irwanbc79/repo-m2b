<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternalMessage;
use App\Services\InternalChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Penyaji lampiran chat internal.
 *
 * File disimpan di disk `local` yang tidak bisa dijangkau lewat URL, jadi
 * SATU-SATUNYA jalan membukanya adalah lewat sini — dan di sini hak aksesnya
 * diperiksa. Japri hanya bisa dibuka pengirim & penerimanya; kalau tidak,
 * URL lampiran yang terlanjur tersebar akan membocorkan isi percakapan
 * pribadi.
 */
class InternalChatFileController extends Controller
{
    public function show(Request $request, int $id)
    {
        $pesan = InternalMessage::findOrFail($id);
        $me    = Auth::user();

        if (! app(InternalChatService::class)->bolehLihatLampiran($me, $pesan)) {
            abort(403, 'Anda tidak berhak membuka lampiran ini.');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($pesan->attachment_path)) {
            abort(404, 'File sudah tidak tersedia.');
        }

        $headers = [
            'Content-Type' => $pesan->attachment_mime ?: 'application/octet-stream',
            // Lampiran chat tidak boleh ikut ter-cache proxy bersama.
            'Cache-Control' => 'private, max-age=0, no-store',
        ];

        // Gambar & PDF ditampilkan langsung; sisanya diunduh.
        $inline = $pesan->lampiranGambar() || $pesan->attachment_mime === 'application/pdf';

        return $inline
            ? response()->file($disk->path($pesan->attachment_path), $headers)
            : response()->download($disk->path($pesan->attachment_path), $pesan->attachment_name, $headers);
    }
}
