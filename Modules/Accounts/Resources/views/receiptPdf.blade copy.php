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
    <div>
        <div style="margin-bottom: 2em">
            <div class="clearfix">
                <div style="float:left">
                    <img src={{ public_path('/dist/img/Asset5.png') }} style="width:200px;height:60px" />
                </div>
                <div style="float:right">

                    <strong>(w) 1111 2222</strong>
                    <br />
                    <div>
                        www.myday.com
                    </div>
                    <div>
                        reply@myday.com
                    </div>
                    <div>
                        {{ $property_address->value }} <br />
                    </div>
                    <div>
                        ABN:
                    </div>
                    <div>
                        Licence:
                    </div>
                </div>
            </div>
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <span style="float:left">
                    <br />
                    {{ $owner_contacts['reference'] }} <br />
                    {{ $owner_address->value }}
                </span>

                <div style="float:right; margin-right: 40px">
                    <span style="font-weight: bold; font-size: 1.5rem">Tax Invoice</span><br>
                    Account {{ $owner_folio->code }}<br />
                    Statement #25 <br />
                    6 Feb 2023
                </div>
            </div>
            <div class="clearfix">
                <div style="width: 50%; float:left">
                    <span style="font-weight:bolder; font-size:16px">{{ $property_address->value }}</span><br>
                    Rented for ${{ $tenant->rent }} per {{ $tenant->rent_type }} <br>
                    Tenant {{ $tenant->tenantContact->reference }} is paid to {{ $tenant->paid_to }}
                </div>
                <div style="width: 50%; float:right">
                    <table class="table1">
                        <tr>
                            <th class="th1">Money In</th>
                            <th class="th1">Money Out</th>
                            <th class="th1">You Received</th>
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
                <th class="th2" colspan="8">Details for Account {{ $owner_folio->code }}</th>
                <th class="th2" colspan="2">Money out</th>
                <th class="th2" colspan="2">Money in</th>
            </tr>
            <tr>
                <td class="td2" colspan="8">Balance brought forward</td>
                <td class="td2" colspan="2"></td>
                <td class="td2" colspan="2">${{ $opening_balance }}</td>
            </tr>
            <tr>
                <td class="td2" colspan="8">Total Rent</td>
                <td class="td2" colspan="2"></td>
                <td class="td2" colspan="2">${{ $rent->amount }}</td>
            </tr>
            <tr>
                <td class="td2" colspan="8">Total Folio Receipt</td>
                <td class="td2" colspan="2"></td>
                <td class="td2" colspan="2">${{ $deposit }}</td>
            </tr>
            <tr>
                <td class="td2" colspan="8">Total Folio Withdraw</td>
                <td class="td2" colspan="2">${{ $withdraw }}</td>
                <td class="td2" colspan="2"></td>
            </tr>
            @foreach ($invoices as $invoice)
                <tr>
                    <td class="td2" colspan="8">{{ $invoice['description'] }}</td>
                    <td class="td2" colspan="2"></td>
                    <td class="td2" colspan="2">${{ $invoice['amount'] }}</td>
                </tr>
            @endforeach
            @foreach ($bills as $bill)
                <tr>
                    <td class="td2" colspan="8">{{ $bill->name }}</td>
                    <td class="td2" colspan="2">${{ $bill->amount }}</td>
                    <td class="td2" colspan="2"></td>
                </tr>
            @endforeach

            <tr>
                <td class="td2" style="font-weight: bold" colspan="8">Total</td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $money_out->amount }}</td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $money_in->amount }}</td>
            </tr>
            <tr>
                <td class="td2" style="font-weight: bold" colspan="8">Balance remaining</td>
                <td class="td2" style="font-weight: bold" colspan="2"></td>
                <td class="td2" style="font-weight: bold" colspan="2">${{ $remaining_balance }}</td>
            </tr>
        </table>

    </div>
    </div>

</body>


<style>
    body {
        background-color: #ffffff;
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
