<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Api\ApiController;
use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\Leave::class;
    protected $validationRules = [];

    public function index(Request $request)
    {
        return $this->crudIndex($request);
    }

    public function store(Request $request)
    {
        return $this->crudStore($request);
    }

    public function show($id)
    {
        return $this->crudShow($id);
    }

    public function update(Request $request, $id)
    {
        return $this->crudUpdate($request,$id);
    }

    public function destroy($id)
    {
        return $this->crudDestroy($id);
    }

    public function getOptimalDates(Request $request)
    {
        try {
            // Call the Django AI Backend
            $response = \Illuminate\Support\Facades\Http::post(env('DJANGO_AI_URL', 'http://localhost:8000') . '/api/ai/leave/optimal-dates/', [
                'employee_id' => auth()->id() ?? 1,
            ]);

            if ($response->successful()) {
                return $this->successResponse($response->json());
            }

            return response()->json(['error' => 'Failed to connect to AI Service'], 502);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
