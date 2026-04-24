<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldPhoto;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FieldDocController extends Controller
{
    /**
     * Cari shipment berdasarkan AWB / BL number (autocomplete).
     */
    public function searchShipments(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $shipments = Shipment::with('customer')
            ->where(function ($query) use ($q) {
                $query->where('awb_number', 'LIKE', "%{$q}%")
                      ->orWhere('bl_number',  'LIKE', "%{$q}%");
            })
            ->whereNotIn('status', ['cancelled', 'cancel'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'awb_number', 'bl_number', 'origin', 'destination',
                   'service_type', 'status', 'customer_id']);

        $data = $shipments->map(fn($s) => [
            'id'           => $s->id,
            'awb_number'   => $s->awb_number,
            'bl_number'    => $s->bl_number,
            'display'      => $s->awb_number ?: $s->bl_number,
            'customer'     => $s->customer?->company_name ?? '-',
            'origin'       => $s->origin,
            'destination'  => $s->destination,
            'service_type' => $s->service_type,
            'status'       => $s->status,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Upload foto lapangan untuk shipment tertentu.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'photo'       => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:100',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);

        $file = $request->file('photo');
        $path = $file->store('field-photos', 'public');

        $photo = FieldPhoto::create([
            'shipment_id'       => $request->shipment_id,
            'user_id'           => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
            'description'       => $request->input('description'),
            'category'          => $request->input('category', 'lapangan'),
            'latitude'          => $request->input('latitude'),
            'longitude'         => $request->input('longitude'),
            'upload_ip'         => $request->ip(),
            'status'            => 'active',
        ]);

        $photo->load('shipment');

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil diupload.',
            'data'    => [
                'id'           => $photo->id,
                'file_url'     => $photo->file_url,
                'description'  => $photo->description,
                'category'     => $photo->category,
                'has_location' => $photo->hasLocation(),
                'shipment'     => $photo->shipment->awb_number ?: $photo->shipment->bl_number,
                'uploaded_at'  => $photo->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Riwayat upload milik user yang sedang login.
     */
    public function history(Request $request): JsonResponse
    {
        $photos = FieldPhoto::where('user_id', $request->user()->id)
            ->with('shipment:id,awb_number,bl_number,origin,destination')
            ->orderByDesc('created_at')
            ->paginate(20);

        $data = $photos->map(fn($p) => [
            'id'           => $p->id,
            'thumbnail_url'=> $p->thumbnail_url,
            'file_url'     => $p->file_url,
            'description'  => $p->description,
            'category'     => $p->category,
            'has_location' => $p->hasLocation(),
            'shipment'     => [
                'id'          => $p->shipment?->id,
                'display'     => $p->shipment?->awb_number ?: $p->shipment?->bl_number ?? '-',
                'origin'      => $p->shipment?->origin,
                'destination' => $p->shipment?->destination,
            ],
            'uploaded_at'  => $p->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success'      => true,
            'data'         => $data,
            'total'        => $photos->total(),
            'current_page' => $photos->currentPage(),
            'last_page'    => $photos->lastPage(),
        ]);
    }
}
