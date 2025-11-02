<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Trading\Risk\PanicService;

class PanicController extends Controller
{
    public function flatten(PanicService $panicService)
    {
        $results = $panicService->flattenAll();

        return response()->json([
            'message' => 'Panic executed',
            'results' => $results,
        ]);
    }
}
