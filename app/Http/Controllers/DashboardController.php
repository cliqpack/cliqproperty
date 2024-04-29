<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\BankDepositList;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Disbursement;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\ReconcilliationMonths;
use Modules\Accounts\Entities\Withdrawal;
use Modules\Contacts\Entities\BuyerFolio;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Contacts\Entities\TenantProperty;
use Modules\Inspection\Entities\Inspection;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Messages\Emails\MessageWithMail;
// use Modules\Messages\Emails\MessageWithMail;
use Modules\Messages\Entities\MessageWithMail as EntitiesMessageWithMail;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyCheckoutKey;
use Modules\Properties\Entities\ReminderProperties;
use Modules\Tasks\Entities\Task;


class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // return "hello";
            $date = date("Y-m-d");
            $newDate = date('Y-m-d', strtotime($date . '-' . '1 months'));
            $prevTwoMonth = date('Y-m-d', strtotime($date . '-' . '2 months'));
            $prevDay = date('Y-m-d', strtotime($date . '-' . '1 days'));
            $disbursement = OwnerFolio::where('next_disburse_date', '<=', date('Y-m-d'))->where('status', true)->where('company_id', auth('api')->user()->company_id)->count();
            $banking = BankDepositList::select('*')->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->sum('amount');
            $bill = Bill::where('company_id', auth('api')->user()->company_id)->sum('amount');
            $job = Maintenance::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'Closed')->count();

            $totalProperties = Properties::where('company_id', auth('api')->user()->company_id)->count();

            $totalArrears = TenantFolio::select('paid_to', DB::raw('count(*) as total'))->whereBetween('paid_to', [$newDate, $prevDay])->where('company_id', auth('api')->user()->company_id)->groupBy('paid_to')->get();
            $arrears      = TenantFolio::where('paid_to', '<', date('Y-m-d'))->where('company_id', auth('api')->user()->company_id)->count();
            $total_tenant = TenantFolio::where('company_id', auth('api')->user()->company_id)->count();
            $owners       = OwnerFolio::where('company_id', auth('api')->user()->company_id)->count();
            $suppliers       = SupplierDetails::where('company_id', auth('api')->user()->company_id)->count();
            $sellers       = SellerFolio::where('company_id', auth('api')->user()->company_id)->count();
            $buyers       = BuyerFolio::where('company_id', auth('api')->user()->company_id)->count();

            $dueTask = Task::where('company_id', auth('api')->user()->company_id)->where('status','!=', 'closed')->count();

            $div = 0;
            if ($arrears != 0 && $total_tenant != 0) {
                $div = $arrears / $total_tenant;
            }

            $rate = $div * 100;

            $mails = EntitiesMessageWithMail::where('company_id', auth('api')->user()->company_id)->where('status', 'Outbox')->count();
            $totalWithdrawal = Withdrawal::select('*')->where('status', false)->where('company_id', auth('api')->user()->company_id)->count();

            // VACANCIES
            $tenantProperty = TenantProperty::where('status', 'true')->pluck('property_id')->toArray();
            $propsWithTenant = Properties::where('status',  'Vacancies')->where('company_id', auth('api')->user()->company_id)->orWhereNotIn('id', $tenantProperty)->pluck('id')->toArray();
            $totalVacancies = Properties::with('tenant.tenantFolio', 'ownerOne.ownerFolio')->whereIn('id', $propsWithTenant)->where('company_id', auth('api')->user()->company_id)->count();
            // return $totalVacancies;

            $vacancyPercentage = 0;
            if ($totalVacancies > 0) {
                $vacancyPercentage = ($totalVacancies / $totalProperties) * 100;
            }
            $vacancies = intval($vacancyPercentage);
            // --------------
            $reportedMaintenance = Maintenance::where('status', 'Reported')->where('company_id', auth('api')->user()->company_id)->count();

            $billsOverdue = Bill::where('status', 'Unpaid')->where('billing_date', '<', $newDate)->where('uploaded', NULL)->where('company_id', auth('api')->user()->company_id)->count();
            $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->count();
            //checkout Properties
            $checkoutProperty = PropertyCheckoutKey::where('check_type', 'out')->pluck('property_id')->toArray();
            // return $checkoutProperty;
            $propertiesWithCompany = Properties::where('company_id', auth('api')->user()->company_id)->WhereIn('id', $checkoutProperty)->pluck('id')->toArray();
            // return $propertiesWithCompany;

            $checkout = PropertyCheckoutKey::where('check_type', 'out')->WhereIn('property_id', $propertiesWithCompany)->count();
            //checkout property end
            $inspectionTask = Inspection::where('company_id', auth('api')->user()->company_id)->where('status','!=','complete')->count();
            //invoice arreas
            // $invoiceAmount      = Invoices::where('status', 'unpaid')->where('invoice_billing_date', '>',  $newDate)->where('company_id', auth('api')->user()->company_id)->sum('amount');
            // if ($invoiceAmount != 0 && $total_tenant != 0) {
            //     $div = $invoiceAmount / $total_tenant;
            // }
            // $invoiceArrears = $div * 100;
            // return "hello";
            $invoicePropertyIds = Invoices::distinct('property_id')
                ->where('status', 'Unpaid')
                ->pluck('property_id')
                ->count();

            // return  $invoicePropertyIds;
            // $invoiceAmount = Invoices::where('status', 'unpaid')
            //     ->where('invoice_billing_date', '>', $newDate)
            //     ->where('company_id', auth('api')->user()->company_id)
            //     ->count('property_id');
            // return  $invoiceAmount;

            // $invoiceArrearsPercentage = ($invoiceAmount / ($total_tenant * $totalProperties)) * 100;
            $invoiceArrears = 0;
            if ($invoicePropertyIds > 0) {
                if ($totalProperties != 0) {
                    $invoiceArrears = ($invoicePropertyIds / $totalProperties) * 100;
                }
            }
            // return  $invoiceArrears;


            // $totalProperties = Properties::count(); // Total count of properties
            // $propertiesWithArrears = Property::whereHas('tenants.invoices', function ($query) {
            //     $query->where('status', 'unpaid')->where('invoice_billing_date', '>', Carbon::now());
            // })->count(); // Count of properties with tenants having unpaid invoices

            // $percentagePropertiesWithArrears = ($propertiesWithArrears / $totalProperties) * 100;


            $today = Date('Y-m');
            $reconcile = ReconcilliationMonths::where('company_id', auth('api')->user()->company_id)->where('reconciliation_status', 'approve')->where('date', 'LIKE', '%' . $today . '%')->count();
            // return $reconcillationList;
            $tenant = TenantContact::where('company_id', auth('api')->user()->company_id)->where('status', 'true')->pluck('id')->toArray();
            // return $tenant;
            $tenantFolioRent = TenantFolio::whereIn('tenant_contact_id', $tenant)->where('next_rent_review', 'LIKE', '%' . $today . '%')->count();
            $rent_review = $tenantFolioRent;


            $currentDate = Carbon::now();
            $upcomingLeaseRenewalsCount = TenantFolio::where('agreement_end', '<=', $currentDate->copy()->addDays(60))->where('company_id', auth('api')->user()->company_id)->count();
            // return  $upcomingLeaseRenewalsCount;


            //inspection Planning start
            $currentDate = Carbon::now();

            $dueDate = $currentDate->copy()->addDays(30);

            $propertiesDueIn30Days = Inspection::select('property_id')
                ->distinct()

                ->where('company_id', auth('api')->user()->company_id)
                ->where('inspection_type', 'Routine')
                ->where('status', 'Scheduled')
                ->whereDate('inspection_date', '>=', $currentDate)
                ->whereDate('inspection_date', '<=', $dueDate)
                ->count();

            $propertiesOverdue = Inspection::select('property_id')
                ->distinct()
                ->where('company_id', auth('api')->user()->company_id)
                ->where('inspection_type', 'Routine')
                ->where('status', 'Scheduled')
                ->whereDate('inspection_date', '<', $currentDate)
                ->count();
            $inspectionPlanning =  $propertiesDueIn30Days + $propertiesOverdue;
            // return $inspectionPlanning;
            //inspection Planning end


            // ----------------------   INSIGHTS API    -----------------------------
            // $insPrevMonthProperties = Properties::select('created_at', DB::raw('count(*) as total, date(created_at) as created'))
            //     ->whereBetween('created_at', [$prevTwoMonth, $newDate])
            //     ->where('company_id', auth('api')->user()->company_id)
            //     ->groupBy(DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y')"))
            //     ->get()
            //     ->map(function ($item) {
            //         return $item->withDefaults();
            //     });

            // $insCurrentMonthProperties = Properties::select('created_at', DB::raw('count(*) as total, date(created_at) as created'))
            //     ->whereBetween('created_at', [$newDate, $date])
            //     ->where('company_id', auth('api')->user()->company_id)
            //     ->groupBy(DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y')"))
            //     ->get()
            //     ->map(function ($item) {

            //         return $item->withDefaults();
            //     });


            // $insPrevMonthProperties = Properties::select('created_at', DB::raw('count(*) as total, date(created_at) as created'))->whereBetween('created_at', [$prevTwoMonth, $newDate])->where('company_id', auth('api')->user()->company_id)->groupBy(DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y')"))->get()->withDefaults();
            // $insCurrentMonthProperties = Properties::select('created_at', DB::raw('count(*) as total, date(created_at) as created'))->whereBetween('created_at', [$newDate, $date])->where('company_id', auth('api')->user()->company_id)->groupBy(DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y')"))->get()->withDefaults();
            // $insPrevMonthProperties = Properties::get()->withDefaults();
            // return $insPrevMonthProperties;
            // ----------------------  END OF INSIGHTS API    -----------------------------
            $inspection_overdue = Inspection::where('status', 'Scheduled')->where('company_id', auth('api')->user()->company_id)->where('inspection_date', '<', $date)->count();
            $maintenanceApproved = Maintenance::where('status', 'Approved')->where('company_id', auth('api')->user()->company_id)->count();
            $maintenanceReported = Maintenance::where('status', 'Reported')->where('company_id', auth('api')->user()->company_id)->count();
            $quoteDue = Maintenance::where('status', 'Quoted')->where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->count();
            // return  $quoteDue;
            // return  $inspection_overdue;



            return response()->json([
                'message' => 'Receipt saved successfully',
                'Status'  => 'Success',
                'disbursement' => $disbursement,
                'banking' => $banking,
                'withdrawal' => $totalWithdrawal,
                'bill' => $bill,
                'suppliers' => $suppliers,
                'sellers' => $sellers,
                'buyers' => $buyers,
                'job' => $job,
                'task' => $dueTask,
                'arrears' => $rate,
                'vacancies' => $vacancies,
                'billsOverdue' => $billsOverdue,
                'inprogressJob' => $reportedMaintenance,
                'outbox' => $mails,
                'owners' => $owners,
                'tenants' => $total_tenant,
                'totalArrears' => $totalArrears,
                'totalProperties' => $totalProperties,
                'reminder' =>  $reminder,
                'checkout' =>  $checkout,
                'inspectionTask' =>  $inspectionTask,
                'invoiceAreas' => $invoiceArrears,
                'inspectionPlanning' => $inspectionPlanning,

                // ---------------- INSIGHTS API
                'insPrevMonthProperties' => [],
                'insCurrentMonthProperties' => [],
                'reconcile' => $reconcile,
                'rent_review' => $rent_review,
                'upcomingLeaseRenewals' => $upcomingLeaseRenewalsCount,
                'inspection_overdue' => $inspection_overdue,
                'maintenance_approved' => $maintenanceApproved,
                'maintenance_reported' => $maintenanceReported,
                'quote_due' => $quoteDue

                // ---------------- END OF INSIGHTS API
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->count();

        $users = User::where('company_id', auth('api')->user()->company_id)->select('id', 'first_name', 'last_name', 'company_id')->get();
        // return $users;

        // $propertiesActivities = PropertyActivity::with('property')
        //     ->whereHas('property', function ($query) {
        //         $query->where('company_id', auth('api')->user()->company_id);
        //     })
        //     ->get();
        $propertiesActivities = PropertyActivity::whereHas('property', function ($query) {
            $query->where('company_id', auth('api')->user()->company_id);
        })->get();
        return $propertiesActivities;
    }
    public function companyManagerWiseActivities(Request $request)
    {
        $propertiesActivities = 0;
        if ($request->id != 'all') {
            $propertiesActivities = PropertyActivity::whereHas('property', function ($query) use ($request) {
                $query->where('company_id', auth('api')->user()->company_id)->where('user_id', $request->id);
            })->get();
        } else {
            $propertiesActivities = PropertyActivity::whereHas('property', function ($query) {
                $query->where('company_id', auth('api')->user()->company_id);
            })->get();
        }

        return $propertiesActivities;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // public function getChartData()
    // {
    //     // return "hello";
    //     $yesterday = Carbon::yesterday();
    //     $lastMonthStart = Carbon::now()->subMonth();
    //     // return $lastMonthStart;
    //     $lastMonthEnd = $yesterday->copy()->subDay();
    //     // return $lastMonthEnd;

    //     // Get data for yesterday
    //     $yesterdayData = Properties::where('status', "Active")->count();

    //     return $yesterdayData;

    //     // Get data for the last month (excluding yesterday)
    //     $lastMonthData = Properties::where('status', 'Active')
    //         ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
    //         ->orderBy('created_at')
    //         ->get();
    //     $lastMonthDataCount = Properties::where('status', 'Active')
    //         ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
    //         ->orderBy('created_at')
    //         ->count();
    //     // return  $lastMonthDataCount;

    //     // Prepare data for the chart
    //     $series1 = [
    //         'name' => 'series1',
    //         'data' => [$yesterdayData],
    //     ];
    //     $series2 = [
    //         'name' => 'series2',
    //         'data' => $lastMonthData->pluck('active_properties_count')->toArray(),
    //     ];

    //     $xAxisCategories = $lastMonthData->pluck('created_at')
    //         ->map(function ($date) {
    //             return $date->format('Y-m-d\TH:i:s.v\Z');
    //         })
    //         ->toArray();

    //     $chartData = [
    //         $series1,
    //         $series2,
    //     ];

    //     return response()->json(['series' => $chartData, 'xaxis' => ['type' => 'datetime', 'categories' => $xAxisCategories]]);
    // }

    public function oldactiveProperties()
    {
        $endDate = Carbon::yesterday();

        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCounts = [];
        $current = clone $startDate;
        $totalCount = 0;
        $previousTotalCount = 0;
        while ($current <= $endDate) {
            $dates[] = clone $current;
            $activePropertyCount = Properties::where('status', 'Active')
                ->whereDate('created_at', $current)
                ->count();
            $totalCount = $previousTotalCount + $activePropertyCount;
            $activePropertyCounts[] = $totalCount;
            $previousTotalCount = $totalCount;
            $current->addDay();
        }
        // return $current;
        // return $date;
        $xAxisCategories = array_map(function ($date) {
            return $date->format('Y-m-d');
        }, array_reverse($dates));
        // return $xAxisCategories;
        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCounts,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => [end($activePropertyCounts)],
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => $xAxisCategories],
        ];

        return response()->json($chartData);
    }
    public function oldlostProperties()
    {
        $ownerProperties = OwnerFolio::where('status', 0)->where('company_id', auth('api')->user()->company_id)->pluck('property_id')->toArray();
        // return $ownerProperties;
        $endDate = Carbon::yesterday();

        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCounts = [];
        $current = clone $startDate;
        $totalCount = 0;
        $previousTotalCount = 0;
        while ($current <= $endDate) {
            $dates[] = clone $current;
            $activePropertyCount = Properties::where('status', 'Archived')
                ->whereDate('created_at', $current)
                ->count();
            $totalCount = $previousTotalCount + $activePropertyCount;
            $activePropertyCounts[] = $totalCount;
            $previousTotalCount = $totalCount;
            $current->addDay();
        }
        // return $current;
        // return $date;
        $xAxisCategories = array_map(function ($date) {
            return $date->format('Y-m-d');
        }, array_reverse($dates));
        // return $xAxisCategories;
        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCounts,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => [end($activePropertyCounts)],
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => $xAxisCategories],
        ];

        return response()->json($chartData);
    }
    public function lostProperties()
    {
        // return auth('api')->user()->company_id;
        $ownerProperties = OwnerFolio::where('status', 0)->where('company_id', auth('api')->user()->company_id)->pluck('property_id')->toArray();
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Properties::where('status', 'Archived')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries1)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = Properties::where('status', 'Archived')
                ->whereDate('updated_at', $currentSeries2)
                ->where('company_id', auth('api')->user()->company_id)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function oldgainProperties()
    {
        $ownerProperties = OwnerFolio::where('status', 1)->where('company_id', auth('api')->user()->company_id)->pluck('property_id')->toArray();
        // return $ownerProperties;
        $endDate = Carbon::yesterday();

        $startDate = today()->subMonth();
        // return $startDate;

        $dates = [];
        $activePropertyCounts = [];
        $current = clone $startDate;
        $totalCount = 0;
        $previousTotalCount = 0;
        while ($current <= $endDate) {
            $dates[] = clone $current;
            $activePropertyCount = Properties::whereIn('id', $ownerProperties)
                ->where('status', 'Active')
                ->whereDate('created_at', $current)
                ->count();
            $totalCount = $previousTotalCount + $activePropertyCount;
            $activePropertyCounts[] = $totalCount;
            $previousTotalCount = $totalCount;
            $current->addDay();
        }
        // return $current;
        // return $date;
        $xAxisCategories = array_map(function ($date) {
            return $date->format('Y-m-d');
        }, array_reverse($dates));
        // return $xAxisCategories;
        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCounts,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => [end($activePropertyCounts)],
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => $xAxisCategories],
        ];

        return response()->json($chartData);
    }
    public function gainProperties()
    {
        $ownerProperties = OwnerFolio::where('status', 1)->where('company_id', auth('api')->user()->company_id)->pluck('property_id')->toArray();
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Properties::whereIn('id', $ownerProperties)
                ->where('status', 'Active')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries1)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = Properties::whereIn('id', $ownerProperties)
                ->where('company_id', auth('api')->user()->company_id)
                ->where('status', 'Active')
                ->whereDate('created_at', $currentSeries2)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }

    // public function getChartData()
    // {
    //     $endDate = Carbon::yesterday();
    //     $startDate = $endDate->copy()->subMonth()->startOfMonth();

    //     $dates = [];
    //     $activePropertyCounts = [];
    //     $current = clone $startDate;
    //     $totalCount = 0;

    //    
    //     while ($current <= $endDate) {
    //         $dates[] = clone $current;
    //         $activePropertyCount = Properties::where('status', 'Active')
    //             ->whereDate('created_at', $current)
    //             ->count();
    //         $totalCount += $activePropertyCount;
    //         $activePropertyCounts[] = $totalCount;
    //         $current->addDay();
    //     }

    //     $xAxisCategories = array_map(function ($date) {
    //         return $date->format('Y-m-d');
    //     }, array_reverse($dates));

    //    
    //     $dailyActivePropertyCounts = Properties::where('status', 'Active')
    //         ->whereBetween('created_at', [$startDate, $endDate])
    //         ->get()
    //         ->groupBy(function ($item) {
    //             return $item->created_at->format('Y-m-d');
    //         })
    //         ->map(function ($group) {
    //             return $group->count();
    //         })
    //         ->values()
    //         ->toArray();

    //     $series1 = [
    //         'name' => 'series1',
    //         'data' => $activePropertyCounts,
    //     ];

    //     $series2 = [
    //         'name' => 'series2',
    //         'data' => $dailyActivePropertyCounts,
    //     ];

    //     $chartData = [
    //         'series' => [$series1, $series2],
    //         'xaxis' => ['type' => 'date', 'categories' => $xAxisCategories],
    //     ];

    //     return response()->json($chartData);
    // }
    // public function getChartDataWithActive()
    // {
    //     $endDate = Carbon::yesterday();
    //     $startDateSeries1 = $endDate->copy()->subDays(30)->startOfMonth();
    //     $startDateSeries2 = $endDate->copy()->subDays(30);

    //     $datesSeries1 = [];
    //     $datesSeries2 = [];
    //     $activePropertyCountsSeries1 = [];
    //     $activePropertyCountsSeries2 = [];
    //     $currentSeries1 = clone $startDateSeries1;
    //     $currentSeries2 = clone $startDateSeries2;
    //     $totalCountSeries1 = 0;
    //     $totalCountSeries2 = 0;

    //     while ($currentSeries1 <= $endDate) {
    //         $datesSeries1[] = clone $currentSeries1;
    //         $dailyActivePropertyCount = Properties::where('status', 'Active')
    //             ->whereDate('created_at', $currentSeries1)
    //             ->count();


    //         $totalCountSeries1 += $dailyActivePropertyCount;
    //         $activePropertyCountsSeries1[] = $totalCountSeries1;

    //         $currentSeries1->addDay();
    //     }

    //     while ($currentSeries2 <= $endDate) {
    //         $datesSeries2[] = clone $currentSeries2;
    //         $dailyActivePropertyCount = Properties::where('status', 'Active')
    //             ->whereDate('created_at', $currentSeries2)
    //             ->count();


    //         $totalCountSeries2 += $dailyActivePropertyCount;
    //         $activePropertyCountsSeries2[] = $totalCountSeries2;

    //         $currentSeries2->addDay();
    //     }

    //     $series1 = [
    //         'name' => 'series1',
    //         'data' => $activePropertyCountsSeries1,
    //     ];

    //     $series2 = [
    //         'name' => 'series2',
    //         'data' => $activePropertyCountsSeries2,
    //     ];

    //     $chartData = [
    //         'series' => [$series1, $series2],
    //         'xaxis' => [
    //             'type' => 'date',
    //             'categories' => array_map(function ($date) {
    //                 return $date->format('Y-m-d');
    //             }, $datesSeries1),
    //         ],
    //         'xaxis1' => array_map(function ($date) {
    //             return $date->format('Y-m-d');
    //         }, $datesSeries2),
    //     ];

    //     return response()->json($chartData);
    // }


    public function getChartDataWithActive()
    {
        $endDate = Carbon::yesterday();
        $startDateSeries1 = $endDate->copy()->subDays(30)->startOfMonth();
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDays(30)->subMonths(1);
        $endDateSeries2 = $startDateSeries2->copy()->addDays(29); // 30 days ago

        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Properties::where('status', 'Active')
                ->whereDate('created_at', $currentSeries1)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = Properties::where('status', 'Active')
                ->whereDate('created_at', $currentSeries2)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = array_slice($datesSeries1, -30);
        $activePropertyCountsSeries1 = array_slice($activePropertyCountsSeries1, -30);
        $datesSeries2 = array_slice($datesSeries2, -30);
        $activePropertyCountsSeries2 = array_slice($activePropertyCountsSeries2, -30);

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function activeProperties()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Properties::where('status', 'Active')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries1)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = Properties::where('status', 'Active')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries2)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function routineInspectionComplete()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        $datesSeries1 = [];
        $datesSeries2 = [];
        $InspectionsCompletedRoutineCountsSeries1 = [];
        $InspectionsCompletedRoutineCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Routine')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries1)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries1 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Routine')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries2)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries2 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        $datesSeries1 = $datesSeries1;
        $InspectionsCompletedRoutineCountsSeries1 = $InspectionsCompletedRoutineCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $InspectionsCompletedRoutineCountsSeries2 = $InspectionsCompletedRoutineCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $InspectionsCompletedRoutineCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $InspectionsCompletedRoutineCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function entryInspectionComplete()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago

        $datesSeries1 = [];
        $datesSeries2 = [];
        $InspectionsCompletedRoutineCountsSeries1 = [];
        $InspectionsCompletedRoutineCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Entry')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries1)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries1 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Entry')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries2)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries2 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        $datesSeries1 = $datesSeries1;
        $InspectionsCompletedRoutineCountsSeries1 = $InspectionsCompletedRoutineCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $InspectionsCompletedRoutineCountsSeries2 = $InspectionsCompletedRoutineCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $InspectionsCompletedRoutineCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $InspectionsCompletedRoutineCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function exitInspectionComplete()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        $datesSeries1 = [];
        $datesSeries2 = [];
        $InspectionsCompletedRoutineCountsSeries1 = [];
        $InspectionsCompletedRoutineCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Exit')
                // ->whereDate('inspection_completed', $currentSeries1)
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries1)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries1 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $InspectionsCompletedRoutineCount = Inspection::where('inspection_type', 'Exit')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('updated_at', $currentSeries2)
                ->where('status', 'complete')
                ->count();

            $totalCountSeries2 += $InspectionsCompletedRoutineCount;
            $InspectionsCompletedRoutineCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        $datesSeries1 = $datesSeries1;
        $InspectionsCompletedRoutineCountsSeries1 = $InspectionsCompletedRoutineCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $InspectionsCompletedRoutineCountsSeries2 = $InspectionsCompletedRoutineCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $InspectionsCompletedRoutineCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $InspectionsCompletedRoutineCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }

    public function conversationOpen()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = EntitiesMessageWithMail::where('watch', 1)
                ->whereDate('created_at', $currentSeries1)
                ->where('company_id', auth('api')->user()->company_id)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = EntitiesMessageWithMail::where('watch', 1)
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries2)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function jobOpen()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Maintenance::where('status', 'Reported')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries1)
                ->count();

            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = EntitiesMessageWithMail::where('watch', 1)
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('created_at', $currentSeries2)
                ->count();

            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function jobAssignedTime()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        // return $totalTimeDifferenceInHours;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            // $dailyActivePropertyCount = Maintenance::where('status', 'Assigned')
            //     ->whereDate('created_at', $currentSeries1)
            //     ->count();

            $assignedMaintenance = Maintenance::where('status', 'Assigned')->whereNotNull('updated_at')->where('company_id', auth('api')->user()->company_id)->whereDate('updated_at', $currentSeries1)->get();
            $assignedMaintenanceCount = Maintenance::where('status', 'Assigned')->where('company_id', auth('api')->user()->company_id)->whereNotNull('updated_at')->whereDate('updated_at', $currentSeries1)->count();

            $totalTimeDifferenceInHours = 0;

            $assignedMaintenance->each(function ($maintenance) use (&$totalTimeDifferenceInHours) {
                $timeDifferenceInHours = $maintenance->updated_at->diffInHours($maintenance->created_at);
                $totalTimeDifferenceInHours += $timeDifferenceInHours;
            });
            $average = 0;
            if ($assignedMaintenanceCount > 0) {
                $average = ($totalTimeDifferenceInHours / $assignedMaintenanceCount);
            } else {
                $average = 0;
            }
            // $average = ($totalTimeDifferenceInHours / $assignedMaintenanceCount) * 100;

            // $totalCountSeries1 = $totalTimeDifferenceInHours;
            $totalCountSeries1 = $average;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            // $dailyActivePropertyCount = EntitiesMessageWithMail::where('watch', 1)
            //     ->whereDate('created_at', $currentSeries2)
            //     ->count();
            $assignedMaintenance = Maintenance::where('status', 'Assigned')->where('company_id', auth('api')->user()->company_id)->whereNotNull('updated_at')->whereDate('updated_at', $currentSeries2)->get();
            $assignedMaintenanceCount = Maintenance::where('status', 'Assigned')->where('company_id', auth('api')->user()->company_id)->whereNotNull('updated_at')->whereDate('updated_at', $currentSeries2)->count();


            $totalTimeDifferenceInHours = 0;

            $assignedMaintenance->each(function ($maintenance) use (&$totalTimeDifferenceInHours) {
                $timeDifferenceInHours = $maintenance->updated_at->diffInHours($maintenance->created_at);
                $totalTimeDifferenceInHours += $timeDifferenceInHours;
            });
            // return $totalTimeDifferenceInHours;
            // $average = ($totalTimeDifferenceInHours / $assignedMaintenanceCount) * 100;
            $average = 0;
            if ($assignedMaintenanceCount > 0) {
                $average = ($totalTimeDifferenceInHours / $assignedMaintenanceCount) * 100;
            } else {
                $average = 0;
            }

            $totalCountSeries2 =  $average;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }



    public function vacancies2()
    {
        return "hello";
    }







    public function routineInspectionCompleteOld()
    {
        // return "hello";
        // $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine');
        // return  $dailyActivePropertyCount;
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine')
                ->whereDate('inspection_completed', $current)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;


            $activePropertyCountsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $dailyActivePropertyCount;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }
    public function oldentryInspectionComplete()
    {
        // return "hello";
        // $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine');
        // return  $dailyActivePropertyCount;
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $dailyActivePropertyCount = Inspection::where('inspection_type', 'Entry')
                ->whereDate('inspection_completed', $current)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;


            $activePropertyCountsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $dailyActivePropertyCount;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }
    public function oldexitInspectionComplete()
    {
        // return "hello";
        // $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine');
        // return  $dailyActivePropertyCount;
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $dailyActivePropertyCount = Inspection::where('inspection_type', 'Exit')
                ->whereDate('inspection_completed', $current)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;


            $activePropertyCountsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $dailyActivePropertyCount;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }
    public function tenantArreas(Request $request)
    {
        // return $request->days;
        // return date('Y-m-d');
        $endDate = Carbon::now('UTC')->subDay(1);
        $days = Carbon::now('UTC')->subDay($request->days);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $arrears      = TenantFolio::where('paid_to', '<', $days)->where('company_id', auth('api')->user()->company_id)->count();
            // return $arrears;
            $total_tenant = TenantFolio::where('company_id', auth('api')->user()->company_id)->count();
            $div = 0;
            if ($arrears != 0 && $total_tenant != 0) {
                $div = $arrears / $total_tenant;
            }

            $rate = intval($div * 100);

            $totalCountSeries1 = $rate;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $arrears      = TenantFolio::where('paid_to', '<', $startDateSeries1)->where('company_id', auth('api')->user()->company_id)->count();
            // return $arrears;
            $total_tenant = TenantFolio::where('company_id', auth('api')->user()->company_id)->count();
            $div = 0;
            if ($arrears != 0 && $total_tenant != 0) {
                $div = $arrears / $total_tenant;
            }

            $rate = intval($div * 100);

            $totalCountSeries2 = $rate;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }


        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }
    public function tenantArreasOld()
    {
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $tenantArrearsSeries1 = [];
        $tenantArrearsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $arrears      = TenantFolio::where('paid_to', '<', date('Y-m-d'))->where('company_id', auth('api')->user()->company_id)->count();
            // return $arrears;
            $total_tenant = TenantFolio::where('company_id', auth('api')->user()->company_id)->count();
            $div = 0;
            if ($arrears != 0 && $total_tenant != 0) {
                $div = $arrears * $total_tenant;
            }

            $rate = $div / 100;


            $totalCountSeries1 += $rate;
            $tenantArrearsSeries1[] = $totalCountSeries1;


            $tenantArrearsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $rate;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $tenantArrearsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $tenantArrearsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }

    public function jobAssigned()
    {
        // return "hello";
        // $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine');
        // return  $dailyActivePropertyCount;
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $dailyActivePropertyCount = Inspection::where('status', 'Assigned')
                // ->whereDate('inspection_completed', $current)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;


            $activePropertyCountsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $dailyActivePropertyCount;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }

    public function taskOverDueOld()
    {
        // return "hello";
        // $dailyActivePropertyCount = Inspection::where('inspection_type', 'Routine');
        // return  $dailyActivePropertyCount;
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subMonth()->startOfMonth();

        $dates = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $current = clone $startDate;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($current <= $endDate) {
            $dates[] = clone $current;
            $dailyActivePropertyCount = Task::where('status', 'Due')
                ->whereDate('due_by', $current)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;


            $activePropertyCountsSeries2[] = $totalCountSeries2;


            $totalCountSeries2 += $dailyActivePropertyCount;

            $current->addDay();
        }

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => ['type' => 'date', 'categories' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $dates)],
        ];

        return response()->json($chartData);
    }
    public function taskOverDue()
    {
        $endDate = Carbon::now('UTC')->subDay(1);
        $startDateSeries1 = $endDate->copy()->subDay(30);
        $endDateSeries1 = $endDate;
        $startDateSeries2 = $endDate->copy()->subDay(60);
        $endDateSeries2 = $endDate->copy()->subDay(30); // 30 days ago
        // return $endDateSeries2;
        $datesSeries1 = [];
        $datesSeries2 = [];
        $activePropertyCountsSeries1 = [];
        $activePropertyCountsSeries2 = [];
        $currentSeries1 = clone $startDateSeries1;
        $currentSeries2 = clone $startDateSeries2;
        $totalCountSeries1 = 0;
        $totalCountSeries2 = 0;

        while ($currentSeries1 <= $endDateSeries1) {
            $datesSeries1[] = clone $currentSeries1;
            $dailyActivePropertyCount = Task::where('status', 'Due')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('due_by', $currentSeries1)
                ->count();


            $totalCountSeries1 += $dailyActivePropertyCount;
            $activePropertyCountsSeries1[] = $totalCountSeries1;

            $currentSeries1->addDay();
        }

        while ($currentSeries2 <= $endDateSeries2) {
            $datesSeries2[] = clone $currentSeries2;
            $dailyActivePropertyCount = Task::where('status', 'Due')
                ->where('company_id', auth('api')->user()->company_id)
                ->whereDate('due_by', $currentSeries2)
                ->count();


            $totalCountSeries2 += $dailyActivePropertyCount;
            $activePropertyCountsSeries2[] = $totalCountSeries2;

            $currentSeries2->addDay();
        }

        // Trim the data arrays to have only 30 days
        $datesSeries1 = $datesSeries1;
        $activePropertyCountsSeries1 = $activePropertyCountsSeries1;
        $datesSeries2 = $datesSeries2;
        $activePropertyCountsSeries2 = $activePropertyCountsSeries2;

        $series1 = [
            'name' => 'series1',
            'data' => $activePropertyCountsSeries1,
        ];

        $series2 = [
            'name' => 'series2',
            'data' => $activePropertyCountsSeries2,
        ];

        $chartData = [
            'series' => [$series1, $series2],
            'xaxis' => [
                'type' => 'date',
                'categories' => array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $datesSeries1),
            ],
            'xaxis1' => array_map(function ($date) {
                return $date->format('Y-m-d');
            }, $datesSeries2),
        ];

        return response()->json($chartData);
    }

    public function testForMe()
    {
        // return auth('api')->user()->company_id;
        $date = date("Y-m-d");
        $newDate = date('Y-m-d', strtotime($date . '-' . '1 months'));
        $prevTwoMonth = date('Y-m-d', strtotime($date . '-' . '2 months'));
        $prevDay = date('Y-m-d', strtotime($date . '-' . '1 days'));

        $invoiceAmount      = Invoices::where('status', 'unpaid')->where('invoice_billing_date', '>',  $newDate)->where('company_id', auth('api')->user()->company_id)->sum('amount');
        // return $invoiceArrears;
        $div = 0;
        // if( $invoiceArrears != 0){

        // }
        $total_tenant = TenantFolio::where('company_id', auth('api')->user()->company_id)->count();
        // return $total_tenant;

        if ($invoiceAmount != 0 && $total_tenant != 0) {
            $div = $invoiceAmount / $total_tenant;
        }

        $invoiceArrears = $div * 100;
        return $invoiceArrears;

        // $totalInvoiceArrears = TenantFolio::select('paid_to', DB::raw('count(*) as total'))->whereBetween('paid_to', [$newDate, $prevDay])->where('company_id', auth('api')->user()->company_id)->groupBy('paid_to')->get();
        $totalInvoiceArrears = TenantFolio::select('paid_to')
            ->selectRaw('count(*) as total')
            ->whereBetween('paid_to', [$newDate, $prevDay])
            ->where('company_id',  auth('api')->user()->company_id)
            ->groupBy('paid_to')
            ->get();
        return $totalInvoiceArrears;

        $checkoutProperty = PropertyCheckoutKey::where('check_type', 'out')->pluck('property_id')->toArray();
        // return $checkoutProperty;
        $propertiesWithCompany = Properties::where('company_id', auth('api')->user()->company_id)->WhereIn('id', $checkoutProperty)->pluck('id')->toArray();
        // return $propertiesWithCompany;

        $checkout = PropertyCheckoutKey::where('check_type', 'out')->WhereIn('property_id', $propertiesWithCompany)->count();
        // return $checkout;
        $job = Maintenance::where('company_id', auth('api')->user()->company_id)->where('status', 'Assigned')->count();

        $inspectionTask = Inspection::where('company_id', auth('api')->user()->company_id)->count();
        // return $inspectionTask;
    }
}
