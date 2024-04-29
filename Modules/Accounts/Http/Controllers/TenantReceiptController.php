<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Interfaces\TenantReceiptRepositoryInterface;

class TenantReceiptController extends Controller
{
    private TenantReceiptRepositoryInterface $tenantReceiptRepository;

    public function __construct(TenantReceiptRepositoryInterface $tenantReceiptRepository)
    {
        $this->tenantReceiptRepository = $tenantReceiptRepository;
    }

    public function store(): JsonResponse
    {
        return response()->json([
            'data' => ''
        ]);
    }
}
