<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    {{-- <meta name="viewport" content="width=device-width, initial-scale=0.5"> --}}
    {{-- <meta http-equiv="X-UA-Compatible" content="ie=edge"> --}}
    {{-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> --}}
    <title>Document</title>
    {{-- <link rel="stylesheet" href="stylepdf.css"> --}}

</head>

<body>
    <div class="page">
        <div style="margin-bottom: 2em">
            <div class="clearfix">
                <div style="float: left; display: flex; align-items: center;">
                    @if(isset($brandLogo['brand_image']) && $brandLogo['brand_image'])
                        <img
                            src="{{ getenv('API_IMAGE') . $brandLogo['brand_image'] }}"
                            style="width: 100px; height: auto; object-fit: cover; border-radius: 8px;"
                            alt="Brand Image">
                    @else
                        <div style="width: 100px; height: 100px; display: flex; justify-content: center; align-items: center; background-color: #f0f0f0; color: #555; border-radius: 8px; font-size: 14px; text-align: center; border: 1px solid #ddd;">
                            No Logo
                        </div>
                    @endif
                </div>

                <div style="float:right; text-align: right; width: calc(100% - 120px);"> <!-- Adjust the width accordingly -->
                    <div>
                        <strong>(w) {{ $user['work_phone'] ?? '61489921018' }}</strong>
                        <br>
                        <span>{{ $user['email'] ?? 'info@cliqproperty.com' }}</span><br>

                        <div>
                            {{ $property_address->value ?? null }} <br />
                        </div>
                        <div>
                            ABN:85 654 549 014
                        </div>
                        <div>
                            Licence: {{ $company['licence_number'] ?? '088702L' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <span style="float:left">
                    <br />
                    {{ $owner_contacts['reference'] ?? null}} <br />
                    {{ $owner_address->value ?? '1234 Main Street, Sydney, NSW 2000' }} <br />

                </span>

                <div style="float:right; margin-right: 40px">
                    <span style="font-weight: bold; font-size: 1.5rem; color: {{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000' }}">Statement</span><br>
                  <span>Tax Invoice</span><br>
                    Account {{ $owner_folio->code ?? null }}<br />
                    Statement #25 <br />
                    6 Feb 2023
                </div>
            </div>
            <div class="clearfix">
                <div style="width: 50%; float:left">
                    <span style="font-weight:bolder; font-size:16px">{{ $property_address->value ?? null }}</span><br>
                    Rented for ${{ $tenant->rent }} per {{ $tenant->rent_type }} <br>
                    Tenant {{ $tenant->tenantContact->reference ?? null }} is paid to {{ $tenant->paid_to ?? null }}
                </div>
                <div style="width: 50%; float:right">
                    <table class="table1">
                        <tr>
                            <th class="th1" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000' }}">Money In</th>
                            <th class="th1" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000'}}">Money Out</th>
                            <th class="th1" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000'}}">You Received</th>
                        </tr>
                        <tr>
                            <td class="td1">${{ $money_in->amount }}</td>
                            <td class="td1">${{ $money_out->amount }}</td>
                            <td class="td1">${{ $payout->amount }}</td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>
        {{-- Property me design --}}
        {{-- <div class="clearfix" style="border-bottom: 2px solid rgb(183, 181, 181); font-weight: bold; font-size: 14px;">
            <div style="float:left">Details for Account OWN00032</div>
            <div style="float:right;">
                <span style="margin-right: 20px">Money out</span>
                <span>Money in</span>
            </div>
        </div>
        <div> --}}
        <table class="table2">
            <tr>
                <th class="th2" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000' }}" colspan="8">Details for Account {{ $owner_folio->code }}</th>
                <th class="th2" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000'}}" colspan="2">Money out</th>
                <th class="th2" style="color:{{ isset($brandStatement->primary_colour) ? $brandStatement->primary_colour : '#000'}}" colspan="2">Money in</th>
            </tr>
            <tr>
                <td class="td2" colspan="8">Balance brought forward</td>
                <td class="td2" colspan="2"></td>
                <td class="td2" colspan="2">${{ $opening_balance }}</td>
            </tr>
            @foreach ($multipleOwnerProperty as $multipleProperty)
                <tr>
                    <div style="margin: 20px 0px;">
                        <span
                            style="font-weight:bolder; font-size:16px">{{ $multipleProperty['property_address']['number'] }}
                            {{ $multipleProperty['property_address']['street'] ? $multipleProperty['property_address']['street'] : '' }}
                            {{ $multipleProperty['property_address']['suburb'] ? $multipleProperty['property_address']['suburb'] : '' }}
                            {{ $multipleProperty['property_address']['state'] ? $multipleProperty['property_address']['state'] : '' }}
                            {{ $multipleProperty['property_address']['postcode'] ? $multipleProperty['property_address']['postcode'] : '' }}</span><br>
                            {{-- {{ $multipleProperty['property_address']['street'] }}
                            {{ $multipleProperty['property_address']['suburb'] }}
                            {{ $multipleProperty['property_address']['state'] }}
                            {{ $multipleProperty['property_address']['postcode'] }} --}}
                        </span><br>
                        Rented for ${{ $multipleProperty['tenant_folio']['rent'] ?? null }} per
                        {{ $multipleProperty['tenant_folio']['rent_type'] ?? null }} <br>
                        Tenant {{ $multipleProperty['tenant_folio']['tenant_contact']['reference']  ?? null}} is paid to
                        {{ $multipleProperty['tenant_folio']['paid_to'] ?? null}}
                    </div>
                    <td>
                        @foreach ($multipleProperty['tenant_folio']['total_property_paid_rent'] as $rent)
                <tr>
                    <td class="td2" colspan="8">Rent {{ $rent['description'] ?? null}}</td>
                    <td class="td2" colspan="2"></td>
                    <td class="td2" colspan="2">${{ $rent['amount'] ?? null}}</td>
                </tr>
            @endforeach
            @foreach ($multipleProperty['tenant_folio']['total_paid_invoice'] as $invoice)
                <tr>
                    <td class="td2" colspan="8">{{ $invoice['description'] ?? null }}</td>
                    <td class="td2" colspan="2"></td>
                    <td class="td2" colspan="2">${{ $invoice['amount'] ?? null}}</td>
                </tr>
            @endforeach
            @foreach ($multipleProperty['property_bill'] as $bill)
                <tr>
                    <td class="td2" colspan="8">{{ $bill['details'] ?? null }}</td>
                    <td class="td2" colspan="2">${{ $bill['amount'] ?? null}}</td>
                    <td class="td2" colspan="2"></td>
                </tr>
            @endforeach
            {{-- <tr>
                <td class="td2" style="font-weight: bold" colspan="8">Total</td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $money_out->amount }}</td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $money_in->amount }}</td>
            </tr> --}}
            </td>
            </tr>
            @endforeach
            <tr>
                <div style="margin: 20px;"></div>
            <tr>
                <td class="td2" colspan="8"><span style="font-weight: bold">Account Transactions</span></td>
                <td class="td2" colspan="2"></td>
                <td class="td2" colspan="2"></td>
            </tr>
            @foreach ($agencyBillList as $agencyBill)
                <tr>
                    <td class="td2" colspan="8">{{ $agencyBill['details'] ?? null}}</td>
                    <td class="td2" colspan="2">${{ $agencyBill['amount']  ?? null }}</td>
                    <td class="td2" colspan="2"></td>
                </tr>
            @endforeach
            @foreach ($totalDepositList as $depositList)
                <tr>
                    <td class="td2" colspan="8">{{ $depositList['description'] ?? null}}</td>
                    <td class="td2" colspan="2"></td>
                    <td class="td2" colspan="2">${{ $depositList['amount'] ?? null }}</td>
                </tr>
            @endforeach
            @foreach ($totalWithdrawList as $withdrawList)
                <tr>
                    <td class="td2" colspan="8">{{ $withdrawList['description'] ?? null }}</td>
                    <td class="td2" colspan="2">${{ $withdrawList['amount'] ?? null}}</td>
                    <td class="td2" colspan="2"></td>
                </tr>
            @endforeach
            </tr>
            <tr>
                <td class="td2" style="font-weight: bold" colspan="8">Balance remaining</td>
                <td class="td2" style="font-weight: bold" colspan="2"></td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $remaining_balance }}</td>
            </tr>
            <tr>
                <div style="margin: 20px;"></div>
            <tr>
                <td class="td2" colspan="2"><span style="font-weight: bold">GST Summary</span></td>
                <td class="td2" colspan="1"></td>
                <td class="td2" colspan="1"></td>
            </tr>
            <tr>
                <td class="td2" colspan="2">Total Tax on income</td>
                <td class="td2" colspan="1">${{ $totalCreditTaxAmount }}</td>
                <td class="td2" colspan="1"></td>
            </tr>
            <tr>
                <td class="td2" colspan="2">Total Tax on attached expenses</td>
                <td class="td2" colspan="1">${{ $totalDebitTaxAmount }}</td>
                <td class="td2" colspan="1"></td>
            </tr>
            <tr>
                <td class="td2" colspan="2">Total Tax on agency fees</td>
                <td class="td2" colspan="1">${{ $totalAgencyBillTaxAmount }}</td>
                <td class="td2" colspan="1"></td>
            </tr>
            <tr><td>( * includes Tax)</td></tr>
            </tr>
        </table>

    </div>
    </div>

</body>


<style>
    body {
        background-color: #ffffff;
    }
    .page {
            background-color: #ffffff;
            width: 21cm;
            /* height: 29.7cm; */
            margin: 1cm auto;
            padding: 1cm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

    .table1 {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    .td1,
    .th1 {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    .table2 {
        border-collapse: collapse;
        width: 100%;
    }

    .th2,
    .td2 {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
    }

    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }

    /* tr:nth-child(even) {
        background-color: #dddddd;
    } */

    .add {
        display: flex;
        justify-content: space-between
    }

    .address2 {
        width: 100%;
        margin-bottom: 50px;
    }

    .balance {
        display: flex;
        justify-content: space-between
    }
</style>

</html>
