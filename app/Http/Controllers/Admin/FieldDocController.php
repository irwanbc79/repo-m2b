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
    public function index(Request $request)
    {
        $todayStr = today()->format('Y-m-d');
        $startOfWeekStr = now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeekStr = now()->endOfWeek()->format('Y-m-d H:i:s');

        $statsQuery = FieldPhoto::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as week,
            SUM(CASE WHEN latitude IS NOT NULL THEN 1 ELSE 0 END) as with_location
        ", [$todayStr, $startOfWeekStr, $endOfWeekStr])->first();

        $stats = [
            'today'         => (int) ($statsQuery->today ?? 0),
            'week'          => (int) ($statsQuery->week ?? 0),
            'total'         => (int) ($statsQuery->total ?? 0),
            'with_location' => (int) ($statsQuery->with_location ?? 0),
        ];

        // Filter inputs
        $search       = trim($request->input('search', ''));
        $serviceType  = $request->input('service_type', '');
        $shipmentType = $request->input('shipment_type', '');
        $dateFrom     = $request->input('date_from', '');
        $dateTo       = $request->input('date_to', '');

        $shipmentsQuery = Shipment::withCount('fieldPhotos')
            ->withMax('fieldPhotos as last_photo_at', 'created_at')
            ->with(['customer', 'latestFieldPhoto'])
            ->whereHas('fieldPhotos', function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereDate('created_at', '>=', $dateFrom);
                if ($dateTo)   $q->whereDate('created_at', '<=', $dateTo);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('awb_number', 'like', "%{$search}%")
                          ->orWhere('bl_number', 'like', "%{$search}%")
                          ->orWhereHas('customer', fn($c) => $c->where('company_name', 'like', "%{$search}%"));
                });
            })
            ->when($serviceType,  fn($q) => $q->where('service_type',  $serviceType))
            ->when($shipmentType, fn($q) => $q->where('shipment_type', $shipmentType))
            // Urut berdasar waktu FOTO terbaru (bukan updated_at shipment) agar
            // shipment dgn foto baru naik ke atas.
            ->orderByDesc('last_photo_at');

        $recentShipments = $shipmentsQuery->paginate(20)->withQueryString();

        $filters = compact('search', 'serviceType', 'shipmentType', 'dateFrom', 'dateTo');

        return view('admin.field-docs.index', compact('stats', 'recentShipments', 'filters'));
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
    public function gallery(Request $request, $shipmentNumber)
    {
        $shipment = Shipment::with('customer')
            ->where('awb_number', $shipmentNumber)
            ->orWhere('bl_number', $shipmentNumber)
            ->orWhere('id', $shipmentNumber)
            ->firstOrFail();

        // Paginated photos
        $photos = FieldPhoto::where('shipment_id', $shipment->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats (from all photos, not paginated)
        $stats = [
            'total' => FieldPhoto::where('shipment_id', $shipment->id)->count(),
            'with_location' => FieldPhoto::where('shipment_id', $shipment->id)->whereNotNull('latitude')->count(),
            'today' => FieldPhoto::where('shipment_id', $shipment->id)->where('created_at', '>=', today())->count(),
        ];

        $canDelete = $this->canDeletePhotos();

        return view('admin.field-docs.gallery', compact('shipment', 'photos', 'stats', 'canDelete'));
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
     * Reassign / Move Photos to Another Shipment
     */
    public function reassignPhotos(Request $request)
    {
        try {
            if (!$this->canDeletePhotos()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk memindahkan foto'
                ], 403);
            }

            $photoIds = $request->input('photo_ids', []);
            $targetShipmentId = $request->input('target_shipment_id');

            if (empty($photoIds) || !$targetShipmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto dan shipment tujuan wajib dipilih'
                ], 400);
            }

            $targetShipment = Shipment::with('customer')->find($targetShipmentId);
            if (!$targetShipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment tujuan tidak ditemukan'
                ], 404);
            }

            $targetLabel = $targetShipment->awb_number ?: $targetShipment->bl_number ?: '#' . $targetShipment->id;
            $updatedCount = FieldPhoto::whereIn('id', $photoIds)->update([
                'shipment_id' => $targetShipment->id,
            ]);

            Log::info('Photos reassigned', [
                'count' => $updatedCount,
                'target_shipment_id' => $targetShipment->id,
                'user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} foto berhasil dipindahkan ke shipment {$targetLabel} (" . ($targetShipment->customer->company_name ?? '-') . ")",
                'target_url' => route('admin.field-docs.gallery', $targetShipment->awb_number ?: $targetShipment->id),
            ]);

        } catch (\Exception $e) {
            Log::error('Reassign photos error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memindahkan foto: ' . $e->getMessage()
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
        $query = trim($request->get('q', ''));

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
            ->orderByRaw("CASE WHEN status NOT IN ('delivered', 'completed', 'cancelled', 'cancel') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return response()->json($shipments->map(function($s) {
            $isActive = !in_array(strtolower($s->status ?? ''), ['delivered', 'completed', 'cancelled', 'cancel']);
            return [
                'id' => $s->id,
                'awb_number' => $s->awb_number,
                'bl_number' => $s->bl_number,
                'display' => $s->awb_number ?: $s->bl_number ?: 'ID #' . $s->id,
                'customer_name' => $s->customer->company_name ?? 'N/A',
                'status' => $s->status ?? 'Draft',
                'is_active' => $isActive,
                'created_at_formatted' => $s->created_at ? $s->created_at->format('d M Y') : '-',
            ];
        }));
    }

    /**
     * Download photos as ZIP
     */
    public function downloadZip(Request $request, $shipment)
    {
        // Find shipment
        $shipmentModel = \App\Models\Shipment::where("awb_number", $shipment)
            ->orWhere("bl_number", $shipment)
            ->orWhere("id", $shipment)
            ->firstOrFail();
        
        // Get photos - either selected IDs or all
        $query = $shipmentModel->fieldPhotos();
        
        if ($request->has("ids")) {
            $ids = explode(",", $request->ids);
            $query->whereIn("id", $ids);
        }
        
        $photos = $query->get();
        
        if ($photos->isEmpty()) {
            return back()->with("error", "Tidak ada foto untuk didownload");
        }
        
        // Create ZIP
        $zipFileName = "dokumentasi_" . ($shipmentModel->awb_number ?: $shipmentModel->bl_number ?: $shipmentModel->id) . "_" . date("Ymd_His") . ".zip";
        $zipPath = storage_path("app/temp/" . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path("app/temp"))) {
            mkdir(storage_path("app/temp"), 0755, true);
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with("error", "Gagal membuat file ZIP");
        }
        
        foreach ($photos as $index => $photo) {
            $filePath = storage_path("app/public/" . $photo->file_path);
            if (file_exists($filePath)) {
                $fileName = ($index + 1) . "_" . ($photo->original_filename ?: basename($photo->file_path));
                $zip->addFile($filePath, $fileName);
            }
        }
        
        $zip->close();
        
        // Download and delete temp file
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}