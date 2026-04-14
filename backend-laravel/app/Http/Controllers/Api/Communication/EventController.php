<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Api\ApiController;
use App\Models\Communication\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->latest('event_date');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($month = $request->query('month')) {
            $year = $request->query('year', now()->year);
            $query->whereMonth('event_date', $month)->whereYear('event_date', $year);
        }

        return $this->successResponse($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'event_date'  => 'required|date',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i|after:start_time',
            'location'    => 'nullable|string|max:255',
            'type'        => 'nullable|string|in:general,meeting,reminder,holiday,birthday,deadline',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
            'user_id'     => 'nullable|exists:users,id',
        ]);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['type']      = $data['type'] ?? 'general';

        $event = Event::create($data);

        return $this->successResponse($event, 'Event created', 201);
    }

    public function show($id): JsonResponse
    {
        return $this->successResponse(Event::findOrFail($id));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'event_date'  => 'sometimes|required|date',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'location'    => 'nullable|string|max:255',
            'type'        => 'nullable|string|in:general,meeting,reminder,holiday,birthday,deadline',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
            'user_id'     => 'nullable|exists:users,id',
        ]);

        $event->update($data);

        return $this->successResponse($event);
    }

    public function destroy($id): \Illuminate\Http\Response
    {
        Event::findOrFail($id)->delete();
        return response()->noContent();
    }
}
