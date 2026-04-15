<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\FieldPhoto;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FieldUploaderController extends Controller
{
    /**
     * Dashboard petugas lapangan — riwayat upload milik sendiri
     */
    public function dashboard()
    {
        $user = Auth::user();

        $totalUploads = FieldPhoto::where('user_id', $user->id)->count();
        $todayUploads = FieldPhoto::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();
        $weekUploads = FieldPhoto::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $recentPhotos = FieldPhoto::where('user_id', $user->id)
            ->with('shipment.customer')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('field.dashboard', compact(
            'totalUploads', 'todayUploads', 'weekUploads', 'recentPhotos'
        ));
    }

    /**
     * Halaman upload — reuse mobile-upload view dengan backUrl ke portal field
     */
    public function upload($shipment = null)
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
            'shipment'       => $selectedShipment,
            'shipmentNumber' => $selectedShipment
                ? ($selectedShipment->awb_number ?: $selectedShipment->bl_number ?: $selectedShipment->id)
                : null,
            'backUrl'        => route('field.dashboard'),
        ]);
    }
}
