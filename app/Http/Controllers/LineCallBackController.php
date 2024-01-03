<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LineService;

class LineCallBackController extends Controller
{
    public function index(Request $request, LineService $lineService)
    {
        return $lineService->webhook($request);
    }
}
