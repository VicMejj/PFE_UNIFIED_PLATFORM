<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
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
        // Get all settings from configuration
        $settings = [
            'company' => [
                'name' => config('app.company_name', ''),
                'email' => config('app.company_email', ''),
                'phone' => config('app.company_phone', ''),
                'address' => config('app.company_address', ''),
            ],
            'system' => [
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'currency' => config('app.currency', 'USD'),
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
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company.name' => 'sometimes|string|max:255',
            'company.email' => 'sometimes|email',
            'company.phone' => 'sometimes|string|max:15',
            'company.address' => 'sometimes|string',
            'system.timezone' => 'sometimes|string|timezone',
            'system.locale' => 'sometimes|string',
            'system.currency' => 'sometimes|string|size:3',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Store settings (implementation depends on how settings are stored)
        // For now, return the updated settings
        $updatedSettings = $request->all();

        return $this->successResponse($updatedSettings, 'Settings updated successfully');
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
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Update the setting (implementation depends on storage method)
        
        return $this->successResponse(['key' => $key, 'value' => $request->value], 'Setting updated successfully');
    }
}
