<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class BroadcastingController extends Controller
{
    public function authenticate(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return Broadcast::auth($request);
    }
}
