<?php

namespace App\Http\Controllers;

use App\Models\FieldPhoto;
use App\Models\Shipment;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;

class FieldDocumentationController extends Controller
{
    /**
     * Dashboard - Halaman utama dokumentasi lapangan
     */
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'today' => FieldPhoto::whereDate('created_at', today())->count(),
            'week' => FieldPhoto::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total' => FieldPhoto::count(),
            'with_gps' => FieldPhoto::whereNotNull('latitude')->count(),
        ];
        
        // Shipments dengan foto terbaru
        $recentShipments = Shipment::withCount('fieldPhotos')
            ->having('field_photos_count', '>', 0)
            ->with(['customer', 'fieldPhotos' => function($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('field-documentation.index', compact('stats', 'recentShipments'));
    }
    
    /**
     * Halaman upload foto
     */
    public function upload($shipment = null)
    {
        return view('field-documentation.upload', [
            'shipmentNumber' => $shipment
        ]);
    }
    
    /**
     * Gallery foto per shipment
     */
    public function gallery($shipment)
    {
        $shipmentData = Shipment::with(['customer', 'fieldPhotos.user'])
            ->where('shipment_number', $shipment)
            ->orWhere('id', $shipment)
            ->firstOrFail();
        
        return view('field-documentation.gallery', [
            'shipment' => $shipmentData,
            'photos' => $shipmentData->fieldPhotos()->latest()->paginate(20)
        ]);
    }
    
    /**
     * API: Search shipments untuk autocomplete
     */
    public function searchShipments(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $shipments = Shipment::with('customer')
            ->where('shipment_number', 'LIKE', "%{$query}%")
            ->orWhereHas('customer', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'shipment_number', 'customer_id']);
        
        return response()->json($shipments->map(function ($s) {
            return [
                'id' => $s->id,
                'shipment_number' => $s->shipment_number,
                'customer_name' => $s->customer->name ?? 'N/A'
            ];
        }));
    }
    
    /**
     * Delete foto (admin only)
     */
    public function deletePhoto(FieldPhoto $photo)
    {
        $this->authorize('delete', $photo);
        
        $imageService = app(ImageProcessingService::class);
        $imageService->deletePhoto($photo);
        $photo->delete();
        
        return back()->with('success', 'Foto berhasil dihapus');
    }
}
