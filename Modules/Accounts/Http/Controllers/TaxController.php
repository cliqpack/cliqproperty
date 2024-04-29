<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaxController extends Controller
{
    public static $gst = 10;
    public function taxCalculation ($amount) {
        $totalPercentage = self::$gst + 100;
        $taxAmount = 0;
        $calc = $amount * self::$gst;
        $taxAmount = $calc/$totalPercentage;
        return round($taxAmount, 2);
    }
}
