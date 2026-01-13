<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldPhoto;
use App\Models\Shipment;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class FieldDocController extends Controller
{
    /**
     * Dashboard
     */
    public function index()
    {
        $stats = [
            'today' => FieldPhoto::whereDate('created_at', today())->count(),
            'week' => FieldPhoto::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total' => FieldPhoto::count(),
            'with_location' => FieldPhoto::whereNotNull('latitude')->count(),
        ];

        $recentShipments = Shipment::withCount('fieldPhotos')
            ->with(['customer'])
            ->whereHas('fieldPhotos')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.field-docs.index', compact('stats', 'recentShipments'));
    }

    /**
     * Upload
     */
    public function upload(Request $request, $shipment = null)
    {
        $selectedShipment = null;
        
        if ($shipment) {
            $selectedShipment = Shipment::with('customer')
                ->where('awb_number', $shipment)
                ->orWhere('bl_number', $shipment)
                ->orWhere('id', $shipment)
                ->first();
        }

        return view('admin.field-docs.upload', [
            'shipment' => $selectedShipment,
            'shipmentNumber' => $selectedShipment ? ($selectedShipment->awb_number ?: $selectedShipment->bl_number ?: $selectedShipment->id) : null,
        ]);
    }

    /**
     * Mobile Upload
     */
    public function mobileUpload($shipment = null)
    {
        $selectedShipment = null;
        
        if ($shipment) {
            $selectedShipment = Shipment::with('customer')
                ->where('awb_number', $shipment)
                ->orWhere('bl_number', $shipment)
                ->orWhere('id', $shipment)
                ->first();
        }

        return view('admin.field-docs.mobile-upload', [
            'shipment' => $selectedShipment,
            'shipmentNumber' => $selectedShipment ? ($selectedShipment->awb_number ?: $selectedShipment->bl_number ?: $selectedShipment->id) : null,
        ]);
    }

    /**
     * Gallery
     */
    public function gallery($shipmentNumber)
    {
        $shipment = Shipment::with(['customer', 'fieldPhotos' => function($q) {
                $q->with('user')->orderBy('created_at', 'desc');
            }])
            ->where('awb_number', $shipmentNumber)
            ->orWhere('bl_number', $shipmentNumber)
            ->orWhere('id', $shipmentNumber)
            ->firstOrFail();

        $stats = [
            'total' => $shipment->fieldPhotos->count(),
            'with_location' => $shipment->fieldPhotos->whereNotNull('latitude')->count(),
            'today' => $shipment->fieldPhotos->where('created_at', '>=', today())->count(),
        ];

        $canDelete = $this->canDeletePhotos();

        return view('admin.field-docs.gallery', compact('shipment', 'stats', 'canDelete'));
    }

    /**
     * Delete Photo - FIXED dengan proper error handling
     */
    public function deletePhoto(Request $request, $photoId)
    {
        Log::info('Delete photo request', ['photo_id' => $photoId, 'user' => auth()->id()]);
        
        try {
            // Check permission
            if (!$this->canDeletePhotos()) {
                Log::warning('Delete permission denied', ['user' => auth()->id()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus foto'
                ], 403);
            }

            $photo = FieldPhoto::find($photoId);
            
            if (!$photo) {
                Log::warning('Photo not found', ['photo_id' => $photoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Foto tidak ditemukan'
                ], 404);
            }

            $shipment = $photo->shipment;
            
            // Delete files
            try {
                $imageService = app(ImageProcessingService::class);
                $imageService->deletePhoto($photo);
            } catch (\Exception $e) {
                Log::error('Failed to delete photo files', ['error' => $e->getMessage()]);
                // Continue anyway - delete DB record even if files fail
            }
            
            // Delete database record
            $photo->delete();
            
            Log::info('Photo deleted successfully', ['photo_id' => $photoId]);

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete photo error', [
                'photo_id' => $photoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Delete Photos
     */
    public function bulkDeletePhotos(Request $request)
    {
        try {
            if (!$this->canDeletePhotos()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus foto'
                ], 403);
            }

            $photoIds = $request->input('photo_ids', []);
            
            if (empty($photoIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada foto yang dipilih'
                ], 400);
            }

            $imageService = app(ImageProcessingService::class);
            $deletedCount = 0;

            foreach ($photoIds as $photoId) {
                $photo = FieldPhoto::find($photoId);
                if ($photo) {
                    try {
                        $imageService->deletePhoto($photo);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete photo file', ['photo_id' => $photoId, 'error' => $e->getMessage()]);
                    }
                    $photo->delete();
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} foto berhasil dihapus"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bulk delete error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if current user can delete photos
     */
    protected function canDeletePhotos(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        $allowedRoles = ['admin', 'owner', 'direktur', 'superadmin'];
        $userRole = strtolower($user->role ?? '');
        
        Log::info('Check delete permission', ['user_role' => $userRole, 'allowed' => $allowedRoles]);
        
        return in_array($userRole, $allowedRoles);
    }

    /**
     * QR Code
     */
    public function qrCode($shipmentNumber)
    {
        $shipment = Shipment::with('customer')
            ->where('awb_number', $shipmentNumber)
            ->orWhere('bl_number', $shipmentNumber)
            ->orWhere('id', $shipmentNumber)
            ->firstOrFail();

        return view('admin.field-docs.qr-code', compact('shipment'));
    }

    /**
     * Download QR
     */
    public function downloadQr($shipmentNumber)
    {
        $shipment = Shipment::where('awb_number', $shipmentNumber)
            ->orWhere('bl_number', $shipmentNumber)
            ->orWhere('id', $shipmentNumber)
            ->firstOrFail();

        $identifier = $shipment->awb_number ?: $shipment->bl_number ?: $shipment->id;
        $uploadUrl = route('admin.field-docs.upload', $identifier);
        
        $qrCode = QrCode::size(300)
            ->format('svg')
            ->generate($uploadUrl);

        $filename = 'QR_' . $identifier . '.svg';

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export PDF
     */
    public function exportPdf($shipmentNumber)
    {
        return redirect()->back()->with('info', 'Export PDF coming soon!');
    }

    /**
     * Search Shipments API
     */
    public function searchShipments(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $shipments = Shipment::with('customer')
            ->where(function($q) use ($query) {
                $q->where('awb_number', 'LIKE', "%{$query}%")
                  ->orWhere('bl_number', 'LIKE', "%{$query}%")
                  ->orWhereHas('customer', function($cq) use ($query) {
                      $cq->where('company_name', 'LIKE', "%{$query}%");
                  });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($shipments->map(function($s) {
            return [
                'id' => $s->id,
                'awb_number' => $s->awb_number,
                'bl_number' => $s->bl_number,
                'customer_name' => $s->customer->company_name ?? 'N/A',
            ];
        }));
    }
}
