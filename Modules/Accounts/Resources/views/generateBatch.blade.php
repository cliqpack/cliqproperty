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
                    {{ $date }}
                </div>
                <div style="float:right">
                    Trust Acc: 12345678
                </div>
            </div>
        </div>

        <div class="address2">
            <div style="text-align: center;">
                <h2>{{ $type }} Statement</h2>
            </div>
            <div style="margin-bottom: 16px; text-align: center;">
                {{-- Batch {{ $id }} Statement created on {{ $date }} --}}
            </div>
            <div>
                <table class="table1">
                    <tr>
                        <th class="th1">Payee</th>
                        <th class="th1">BSB</th>
                        <th class="th1">Account</th>
                        <th class="th1">Amount</th>
                    </tr>
                    @foreach ($withdrawArray as $value)
                        <tr>
                            <td class="td1">{{ $value->payee }}</td>
                            <td class="td1">{{ $value->bsb }}</td>
                            <td class="td1">{{ $value->account }}</td>
                            <td class="td1">${{ $value->amount }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="clearfix" style="margin-top: 70px;">
            <div style="float:left; width: 300px">
                <b>Name: </b><span style="display: inline-block; border-bottom: 1px solid black; width: 200px"></span>
            </div>
            <div style="float:left">
                <div style="width: 300px"><b>Signature: </b><span
                        style="display: inline-block; border-bottom: 1px solid black; width: 200px"></span></div>
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

    .address2 {
        width: 100%;
        margin-bottom: 20px;
    }
</style>

</html>
