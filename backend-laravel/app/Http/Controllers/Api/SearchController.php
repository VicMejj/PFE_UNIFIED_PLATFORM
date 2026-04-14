<?php

namespace App\Http\Controllers\Api;

use App\Models\Communication\Event;
use App\Models\Contract\Contract;
use App\Models\Employee\Employee;
use App\Models\Leave\Leave;
use App\Models\Attendance\TimeSheet;
use App\Models\Payroll\PaySlip;
use App\Models\Payroll\Allowance;
use App\Models\Notification;
use Illuminate\Http\Request;
use Throwable;

class SearchController extends ApiController
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:120',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $q = trim($data['q']);
        $limit = (int) ($data['limit'] ?? 5);
        $like = '%' . addcslashes($q, '%_\\') . '%';

        $employees = $this->safeSearch(function () use ($like, $limit, $q) {
            return Employee::query()
                ->with(['user'])
                ->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('employee_id', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($employee) => [
                    'id' => $employee->id,
                    'type' => 'employee',
                    'title' => trim(collect([$employee->name, $employee->full_name, $employee->email])->filter()->first() ?? "Employee #{$employee->id}"),
                    'subtitle' => $employee->employee_id ?? $employee->email ?? 'Employee record',
                    'route' => "/rh/employees?search={$q}",
                ])
                ->values();
        });

        $leaves = $this->safeSearch(function () use ($like, $limit) {
            return Leave::query()
                ->with(['employee', 'leaveType'])
                ->where(function ($query) use ($like) {
                    $query->where('reason', 'like', $like)
                        ->orWhere('status', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($leave) => [
                    'id' => $leave->id,
                    'type' => 'leave',
                    'title' => ($leave->employee?->name ?? $leave->employee?->full_name ?? "Leave #{$leave->id}"),
                    'subtitle' => trim(($leave->leaveType?->name ?? 'Leave') . ' · ' . $leave->start_date?->toDateString() . ' to ' . $leave->end_date?->toDateString()),
                    'route' => '/leave-requests',
                ])
                ->values();
        });

        $events = $this->safeSearch(function () use ($like, $limit) {
            return Event::query()
                ->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('location', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'type' => 'event',
                    'title' => $event->title ?? "Event #{$event->id}",
                    'subtitle' => $event->location ?? $event->event_date?->toDateString() ?? 'Calendar event',
                    'route' => '/attendance',
                ])
                ->values();
        });

        $contracts = $this->safeSearch(function () use ($like, $limit) {
            return Contract::query()
                ->with(['employee'])
                ->where(function ($query) use ($like) {
                    $query->where('contract_name', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($contract) => [
                    'id' => $contract->id,
                    'type' => 'contract',
                    'title' => $contract->contract_name ?? "Contract #{$contract->id}",
                    'subtitle' => $contract->employee?->name ?? $contract->employee?->full_name ?? 'Contract record',
                    'route' => '/rh/contracts',
                ])
                ->values();
        });

        $payroll = $this->safeSearch(function () use ($like, $limit) {
            return PaySlip::query()
                ->with(['employee'])
                ->where(function ($query) use ($like) {
                    $query->where('status', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($slip) => [
                    'id' => $slip->id,
                    'type' => 'payroll',
                    'title' => $slip->employee?->name ?? "Payroll #{$slip->id}",
                    'subtitle' => sprintf('%s/%s · %s', $slip->payroll_month, $slip->payroll_year, $slip->status ?? 'record'),
                    'route' => '/rh/payroll',
                ])
                ->values();
        });

        $attendance = $this->safeSearch(function () use ($like, $limit) {
            return TimeSheet::query()
                ->with(['employee'])
                ->where(function ($query) use ($like) {
                    $query->where('status', 'like', $like)
                        ->orWhere('project_name', 'like', $like)
                        ->orWhere('task_description', 'like', $like)
                        ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', $like));
                })
                ->limit($limit)
                ->get()
                ->map(fn ($record) => [
                    'id' => $record->id,
                    'type' => 'attendance',
                    'title' => $record->employee?->name ?? "Attendance #{$record->id}",
                    'subtitle' => sprintf('%s · %s', $record->timesheet_date?->toDateString() ?? 'Date', $record->status ?? 'record'),
                    'route' => '/attendance',
                ])
                ->values();
        });

        $messages = $this->safeSearch(function () use ($like, $limit) {
            return Notification::query()
                ->where(function ($query) use ($like) {
                    $query->where('type', 'like', $like)
                        ->orWhere('dedup_key', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => 'message',
                    'title' => $item->payload['title'] ?? $item->type ?? "Message #{$item->id}",
                    'subtitle' => $item->payload['message'] ?? 'Platform message',
                    'route' => '/notifications',
                ])
                ->values();
        });

        $benefits = $this->safeSearch(function () use ($like, $limit) {
            return Allowance::query()
                ->with(['employee', 'allowanceOption'])
                ->where(function ($query) use ($like) {
                    $query->where('status', 'like', $like)
                        ->orWhereHas('allowanceOption', fn ($option) => $option->where('name', 'like', $like))
                        ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', $like));
                })
                ->limit($limit)
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => 'benefit',
                    'title' => $item->allowanceOption?->name ?? "Benefit #{$item->id}",
                    'subtitle' => $item->employee?->name ?? 'Benefit assignment',
                    'route' => '/social/benefits',
                ])
                ->values();
        });

        return $this->successResponse([
            'query' => $q,
            'employees' => $employees->values(),
            'leaves' => $leaves->values(),
            'events' => $events->values(),
            'attendance' => $attendance->values(),
            'contracts' => $contracts->values(),
            'messages' => $messages->values(),
            'payroll' => $payroll->values(),
            'benefits' => $benefits->values(),
        ]);
    }

    private function safeSearch(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }
}
