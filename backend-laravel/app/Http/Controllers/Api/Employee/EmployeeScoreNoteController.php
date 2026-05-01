<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Employee\Employee;
use App\Services\EmployeeScoreNoteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeScoreNoteController extends ApiController
{
    public function __construct(
        private EmployeeScoreNoteService $noteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employeeId = $request->query('employee_id');
        
        if (!$employeeId) {
            return $this->errorResponse('Employee ID is required', 422);
        }

        $employee = Employee::findOrFail($employeeId);
        $user = Auth::user();

        if (!$this->noteService->canUserViewEmployeeNotes($user, $employee)) {
            return $this->errorResponse('Unauthorized to view notes for this employee', 403);
        }

        $authorType = $request->query('author_type');
        $notes = $this->noteService->getNotesForEmployee($employee, $authorType);
        $summary = $this->noteService->getLatestNotesSummary($employee);

        return $this->successResponse([
            'notes' => $notes,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$this->noteService->canUserAddNotes($user)) {
            return $this->errorResponse('You are not authorized to add notes', 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'note_type' => 'nullable|string|in:monthly,quarterly,annual,adhoc',
            'attendance_note' => 'nullable|array',
            'attendance_note.*.rating' => 'nullable|integer|min:1|max:10',
            'attendance_note.*.comment' => 'nullable|string|max:500',
            'discipline_note' => 'nullable|array',
            'discipline_note.*.rating' => 'nullable|integer|min:1|max:10',
            'discipline_note.*.comment' => 'nullable|string|max:500',
            'performance_note' => 'nullable|array',
            'performance_note.*.rating' => 'nullable|integer|min:1|max:10',
            'performance_note.*.comment' => 'nullable|string|max:500',
            'general_note' => 'nullable|string|max:2000',
            'score_adjustment' => 'nullable|integer|min:-20|max:20',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $employee = Employee::findOrFail($request->employee_id);

        if (!$this->noteService->canUserViewEmployeeNotes($user, $employee)) {
            return $this->errorResponse('You are not authorized to add notes for this employee', 403);
        }

        $note = $this->noteService->createNote($employee, $user, $request->all());

        return $this->successResponse([
            'message' => 'Note added successfully',
            'note' => $note,
        ], 201);
    }

    public function myDepartmentEmployees(): JsonResponse
    {
        $user = Auth::user();

        if (!$this->noteService->canUserAddNotes($user)) {
            return $this->errorResponse('You are not authorized to add notes', 403);
        }

        $employees = $this->noteService->getEmployeesForNote($user);

        return $this->successResponse([
            'employees' => $employees,
        ]);
    }

    public function departmentNotes(): JsonResponse
    {
        $user = Auth::user();

        if (!$this->noteService->canUserAddNotes($user)) {
            return $this->errorResponse('You are not authorized to view notes', 403);
        }

        $notes = $this->noteService->getNotesByEmployeeForManager($user);

        return $this->successResponse([
            'notes' => $notes,
        ]);
    }

    public function employeeScoreWithNotes($employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        $user = Auth::user();

        if (!$this->noteService->canUserViewEmployeeNotes($user, $employee)) {
            return $this->errorResponse('Unauthorized to view this employee', 403);
        }

        $score = $employee->score;
        $notes = $this->noteService->getNotesForEmployee($employee);
        $summary = $this->noteService->getLatestNotesSummary($employee);

        return $this->successResponse([
            'employee' => $employee,
            'score' => $score,
            'notes' => $notes,
            'notes_summary' => $summary,
        ]);
    }
}
