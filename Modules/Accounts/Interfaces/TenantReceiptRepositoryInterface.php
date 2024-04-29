<?php
namespace Modules\Accounts\Interfaces;

interface TenantReceiptRepositoryInterface
{
    public function tenantReceiptStore($request);
}