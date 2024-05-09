<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <title>Document</title>
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
                        www.cliqproperty.com
                    </div>
                    <div>
                        reply@cliqproperty.com
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
                <span style="float:left; font-weight: bold; font-size: 1.5rem">
                    <br />
                    Trust Account Receipt
                </span>
            </div>
        </div>
        <div class="clearfix" style="margin-bottom: 20px;">
            <div style="width: 50%; float:left">
                @if ($property_address->value)
                    <span style="font-weight:bolder; font-size:16px">For property:
                        {{ $property_address->value }}</span><br>
                @endif
                Receipt number: {{ $receiptInformation->receiptNumber }} <br>
                Date received: {{ $receiptInformation->receiptDate }} <br>
                On behalf of: {{ $receiptInformation->onBehalfOf }} <br>
                {{ $receiptInformation->folioInfo }}: {{ $receiptInformation->folioName }} <br>
            </div>
        </div>
        <table class="table2">
            <tr>
                <th class="th2">Description</th>
                <th class="th2">Included Tax</th>
                <th class="th2">Amount</th>
            </tr>
            @foreach ($receiptDetails as $details)
                <tr>
                    <td class="td2">{{ $details['description'] }}</td>
                    <td class="td2">${{ $details['taxAmount'] }}</td>
                    <td class="td2">${{ $details['amount'] }}</td>
                </tr>
            @endforeach

            <tr>
                <td class="td2" style="font-weight: bold">Total</td>
                <td class="td2" style="font-weight: bold">${{ $receiptData->totalTaxAmount }}</td>
                <td class="td2" style="font-weight: bold">${{ $receiptData->totalAmount }}</td>
            </tr>
        </table>
        <div class="clearfix" style="margin-top: 20px;">
            <div style="width: 50%; float:left">
                Payment method: {{ $footerDetails->payment_method }} <br>
                Principal: {{ $footerDetails->principal }} <br>
                Company: {{ $footerDetails->company }} <br>
                Receipted by: {{ $footerDetails->principal }} <br>
                Date processed: {{ $receiptInformation->receiptDate }} <br>
            </div>
        </div>

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
        margin-bottom: 20px;
    }

    .balance {
        display: flex;
        justify-content: space-between
    }
</style>

</html>
