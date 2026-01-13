<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * Show public survey form
     */
    public function index()
    {
        return view('survey.public-form');
    }

    /**
     * Show thank you page after survey submission
     */
    public function thankYou()
    {
        return view('survey.thank-you');
    }

    /**
     * Generate QR Code for survey link
     */
    public function generateQrCode()
    {
        $surveyUrl = route('survey.public');
        
        // Generate QR code (will need SimpleSoftwareIO/simple-qrcode package)
        // For now, return the URL
        return response()->json([
            'url' => $surveyUrl,
            'message' => 'QR Code generation - install QR package first'
        ]);
    }
}
