<?php

namespace App\Http\Controllers\Api\Contract;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use Illuminate\Http\Request;

class ContractTypeController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\Contract\ContractType::class;
    protected $validationRules = [];

    protected function authorizeContractManagement()
    {
        $user = auth()->user();

        if ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'rh_manager', 'rh', 'hr', 'manager'])) {
            return null;
        }

        return $this->forbiddenResponse('Only managers, HR, or administrators can manage contract types.');
    }

    public function index(Request $request)
    {
        if ($response = $this->authorizeContractManagement()) {
            return $response;
        }

        return $this->crudIndex($request);
    }

    public function store(Request $request)
    {
        if ($response = $this->authorizeContractManagement()) {
            return $response;
        }

        return $this->crudStore($request);
    }

    public function show($id)
    {
        if ($response = $this->authorizeContractManagement()) {
            return $response;
        }

        return $this->crudShow($id);
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->authorizeContractManagement()) {
            return $response;
        }

        return $this->crudUpdate($request,$id);
    }

    public function destroy($id)
    {
        if ($response = $this->authorizeContractManagement()) {
            return $response;
        }

        return $this->crudDestroy($id);
    }
}
