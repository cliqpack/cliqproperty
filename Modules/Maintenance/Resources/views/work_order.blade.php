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
                    <div style="width: 200px;">
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
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <span style="float:left">
                    <br />
                    <h2>Work Order</h2>
                    {{ $supplier_name }} <br />
                    {{ $supplier_email }}
                </span>

                <div style="float:right; margin-right: 40px;">
                    <br />
                    <br />
                    <span style="font-weight: bold; font-size: 1.5rem;">Job number - 000{{ $job_id }}</span><br>
                    Created on: {{ $job_create_date }} <br />
                    Due: {{ $job_due_date }}
                </div>
            </div>
        </div>
        <hr>
        <div class="clearfix" style="margin-bottom: 20px;">
            <div style="width: 40%;">
                <span style="float:left">
                    <br />
                    <h2>Details</h2>
                    <p>Property</p>
                    <p>&nbsp;&nbsp;&nbsp;{{ $property_name }}</p>
                    <span>For access contact the tenant on:</span>
                    <p>&nbsp;&nbsp;&nbsp;{{ $tenant_name }}</p>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(m){{ $tenant_mobile }}&nbsp;(w){{ $tenant_work }}&nbsp;(h){{ $tenant_home }}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(e){{ $tenant_email }}</span>
                    <p>Work order issued on behalf of the owner - {{ $owner_name }}</p>
                </span>
            </div>

            <div style="float:right; margin-right: 40px;">
                <div>
                    <br /><br /><br /><br />
                    <p>For queries contact the agent on:</p>
                    <span>&nbsp;&nbsp;&nbsp;{{$manager_name}}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(m){{ $manager_mobile }}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(e){{ $manager_email }}</span><br>
                </div>
            </div>
        </div>
        <hr>
        <div>
            <h2>Description</h2>
            <span>&nbsp;&nbsp;Summary</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$summary}}</span><br><br>
            <span>&nbsp;&nbsp;Description</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$description}}</span><br><br>
            <span>&nbsp;&nbsp;Note</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$note}}</span>
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
