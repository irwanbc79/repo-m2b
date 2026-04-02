<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'notes'       => 'nullable|string|max:1000',
            'visited_at'  => 'nullable|date',
            'photo'       => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('visits/photos', 'public');
        }

        $visit = ClientVisit::create([
            'user_id'     => $request->user()->id,
            'client_name' => $request->client_name,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'photo_path'  => $photoPath,
            'notes'       => $request->notes,
            'visited_at'  => $request->input('visited_at', now()),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan berhasil dicatat.',
            'data'    => $visit,
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $visits = ClientVisit::where('user_id', $request->user()->id)
            ->whereYear('visited_at', $year)
            ->whereMonth('visited_at', $mon)
            ->orderBy('visited_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat kunjungan.',
            'data'    => $visits,
        ]);
    }
}
