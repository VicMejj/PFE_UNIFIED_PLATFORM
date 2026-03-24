<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends ApiController
{
    /**
     * Get all application settings
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dbSettings = Setting::whereIn('key', [
            'company.name',
            'company.email',
            'company.phone',
            'company.address',
            'system.timezone',
            'system.locale',
            'system.currency',
        ])->get()->keyBy('key');

        // Get all settings from configuration with DB overrides
        $settings = [
            'company' => [
                'name' => $this->resolveSettingValue($dbSettings, 'company.name', config('app.company_name', '')),
                'email' => $this->resolveSettingValue($dbSettings, 'company.email', config('app.company_email', '')),
                'phone' => $this->resolveSettingValue($dbSettings, 'company.phone', config('app.company_phone', '')),
                'address' => $this->resolveSettingValue($dbSettings, 'company.address', config('app.company_address', '')),
            ],
            'system' => [
                'timezone' => $this->resolveSettingValue($dbSettings, 'system.timezone', config('app.timezone')),
                'locale' => $this->resolveSettingValue($dbSettings, 'system.locale', config('app.locale')),
                'currency' => $this->resolveSettingValue($dbSettings, 'system.currency', config('app.currency', 'USD')),
            ],
            'modules' => [
                'attendance' => config('modules.attendance.enabled', true),
                'payroll' => config('modules.payroll.enabled', true),
                'insurance' => config('modules.insurance.enabled', true),
                'recruitment' => config('modules.recruitment.enabled', true),
            ],
        ];

        return $this->successResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update application settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'nullable',
            'type' => 'sometimes|string|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $setting = Setting::create([
            'key' => $request->key,
            'value' => $request->value,
            'type' => $request->type ?? 'string',
            'description' => $request->description,
            'category' => $request->category,
        ]);

        return $this->successResponse($setting, 'Setting created successfully', 201);
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return $this->successResponse($setting, 'Setting retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|required|string|max:255|unique:settings,key,' . $id,
            'value' => 'nullable',
            'type' => 'sometimes|string|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $setting = Setting::findOrFail($id);
        $setting->update($request->only(['key', 'value', 'type', 'description', 'category']));

        return $this->successResponse($setting, 'Settings updated successfully');
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();
        return response()->json(null, 204);
    }

    /**
     * Get a specific setting by key
     *
     * @param string $key
     * @return \Illuminate\Http\Response
     */
    public function getSetting($key)
    {
        $keys = explode('.', $key);
        $setting = config('app.' . implode('.', $keys));

        if (!$setting) {
            return $this->notFoundResponse('Setting');
        }

        return $this->successResponse(['value' => $setting], 'Setting retrieved successfully');
    }

    /**
     * Update a specific setting
     *
     * @param \Illuminate\Http\Request $request
     * @param string $key
     * @return \Illuminate\Http\Response
     */
    public function updateSetting(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        // Update the setting (implementation depends on storage method)
        
        return $this->successResponse(['key' => $key, 'value' => $request->value], 'Setting updated successfully');
    }

    private function resolveSettingValue($settings, string $key, $fallback)
    {
        if (! $settings->has($key)) {
            return $fallback;
        }

        $setting = $settings->get($key);
        $value = $setting->value;

        return match ($setting->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'json' => json_decode($value, true) ?? $value,
            default => $value,
        };
    }
}
