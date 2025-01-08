<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.5">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Document</title>
    <link rel="stylesheet" href="stylepdf.css">

</head>

<body>
    <div>
        <div style="margin-bottom: 2em">
            <div class="clearfix">
                <div style="float:left">
                    <img src="{{ getenv('API_IMAGE').$brandLogo['brand_image'] ?? null}}" style="width: 100px;  object-fit: cover;" alt="Brand Image">
                </div>
                <div style="float:right">
                    <div>
                        <strong>(w) {{ $user['work_phone'] ? $user['work_phone'] : '1112222333' }}</strong>
                        <br>
                        <div>
                            {{ $user['email'] ? $user['email'] : 'info@myday.com' }}
                        </div>
                        <div>
                            ABN:85 654 549 014
                        </div>
                        <div>
                            Licence:{{ $company['licence_number'] ? $company['licence_number'] : '088702L' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="address2">
            {{-- @dd($data) --}}
            <br>
            <h4 class="text-center">Routine Report</h4>
            <br>
            @if (count($data) > 0)
                @foreach ($data as $d)
                    <br>
                    <strong>{{ $d['room'] }}</strong>
                    <p>Description: {{ $d['routine_description'] }}</p>
                    {{-- <p>{{$d["image"]}}</p> --}}
                    {{-- @foreach ($d['image'] as $img)
                <img src="{{ public_path('/public/image/')}}/{{$img}}" alt="" style="width:200px;height:60px">
            @endforeach --}}

                    <h5>Image: </h5>
                    @if (count($d['image']) > 0)
                        @foreach ($d['image'] as $img)
                            <img src="{{config('app.api_url_server')}}{{ $img }}" alt=""
                                style="width:200px;height:160px;">
                        @endforeach
                    @else
                        <p>No Image Found</p>
                    @endif
                @endForeach
            @else
                <h5>No data Found!! This report is fresh. Please Insert Report Data</h5>
            @endif

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
        border-color: #32383e;
    }

    .table2 th {
        color: #fff;
        background-color: #212529;
        border-color: #32383e;
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

    h4 {
        text-align: center;
    }

    table,
    th,
    td {
        border: 1px solid black;
    }
</style>



</html>
