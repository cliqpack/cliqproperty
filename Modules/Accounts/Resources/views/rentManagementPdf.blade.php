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
                    <div style="width: 200px">
                        <strong>(w) 1111 2222</strong>
                        <br />
                        <div>
                            www.myday.com
                        </div>
                        <div>
                            reply@myday.com
                        </div>
                        <div>
                            {{ $propAddress }} <br />
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
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <span style="float:left">
                    <br />
                    <h3>Tenant: {{ $tenant_name }}</h3>
                    {{-- {{ $tenant_name }} <br /> --}}
                </span>

                <div style="float:right; margin-right: 40px">
                    <span style="font-weight: bold; font-size: 1.5rem">Tax Invoice</span><br><br>
                    Folio: {{ $tenant_folio }}<br />
                    Created date: {{ $created_date }} <br />
                    Invoice# : 000{{ $rent_management->id }}
                </div>
            </div>
            <div>
                <table class="table1">
                    <tr>
                        <th class="th1" style="text-align: center;">
                            Property
                        </th>
                        <th class="th1">Due date</th>
                        <th class="th1">Due</th>
                    </tr>
                    <tr>
                        <td class="td1">{{ $propAddress }}</td>
                            <td class="td1">{{ $rent_management->from_date }}</td>
                            <td class="td1">${{ $totalDueAmount }}</td>
                    </tr>

                </table>
            </div>
        </div>
        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left">
                <b>Description</b>
            </div>
            <div style="float:right">
                <div style="width: 100px">Amount</div>
            </div>
            <div style="float:right">
                <div style="width: 200px">Include tax</div>
            </div>
        </div>
        <hr>
        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left">
                Rent from {{$rent_management->from_date}} to {{$rent_management->to_date}}
            </div>
            <div style="float:right">
                <div style="width: 100px">${{ $rent_management->due }}</div>
            </div>
            <div style="float:right">
                <div style="width: 200px">$0.00</div>
            </div>
        </div>
        <hr>
        @foreach ($rent_management->invoices as $item)
            <div class="clearfix" style="margin-top: 20px;">
                <div style="float:left">
                    {{$item['details']}}
                </div>
                <div style="float:right">
                    <div style="width: 100px">${{ $item['amount'] }}</div>
                </div>
                <div style="float:right">
                    <div style="width: 200px">${{ $item['taxAmount'] }}</div>
                </div>
            </div>
            <hr>
        @endforeach
        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left">
                {{-- {{$description}} --}}
            </div>
            <div style="float:right">
                <div style="width: 100px">${{ $totalAmount }}</div>
            </div>
            <div style="float:right">
                <div style="width: 350px">Subtotal:</div>
            </div>
        </div>
        <hr>
        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left">
                {{-- {{$description}} --}}
            </div>
            <div style="float:right">
                <div style="width: 100px">${{ $totalPaidAmount }}</div>
            </div>
            <div style="float:right">
                <div style="width: 350px">Amount paid:</div>
            </div>
        </div>
        <hr>
        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left">
                {{-- {{$description}} --}}
            </div>
            <div style="float:right">
                <div style="width: 100px">${{ $totalDueAmount }}</div>
            </div>
            <div style="float:right">
                <div style="width: 350px">Amount due:</div>
            </div>
        </div>
        <hr>
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
