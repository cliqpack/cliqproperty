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
                    <img src="{{ getenv('API_IMAGE') . $brandLogo['brand_image'] ?? null }}"
                        style="width: 100px;  object-fit: cover;" alt="Brand Image">
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
            {{-- @dd($data); --}}
            <br>
            <h4 class="text-center">Exit Report</h4>
            <br>
            @if (count($data) > 0)
                @foreach ($data as $d)
                    {{-- @dd($d['all']); --}}
                    @foreach ($d['all'] as $all)
                        {{-- @dd($all); --}}
                        <strong>{{ $all['room'] }}</strong>
                        <p>Description: {{ $all['description'] }}</p>
                        {{-- <br> --}}
                        {{-- @dd($all['details']); --}}
                        <table class="table2">
                            <tr>
                                <th class="th2"></th>
                                <th class="th2">Clean </th>
                                <th class="th2">Undamage</th>
                                <th class="th2">Working</th>
                                <th class="th2">Comment</th>
                            </tr>
                            @foreach ($all['details'] as $a)
                                {{-- @dd($a) --}}


                                <tr class="td2">
                                    <td class="td2">{{ $a['room_attributes'] }}</td>
                                    <td class="td2">
                                        @if ($a['clean'] == '1')
                                            {{ 'Yes' }}
                                        @elseif ($a['clean'] == '0')
                                            {{ 'No' }}
                                        @elseif ($a['clean'] == '')
                                            {{ ' ' }}
                                        @endif
                                    </td>
                                    <td class="td2">
                                        @if ($a['undamaged'] == '1')
                                            {{ 'Yes' }}
                                        @elseif ($a['undamaged'] == '0')
                                            {{ 'No' }}
                                        @elseif ($a['undamaged'] == '')
                                            {{ ' ' }}
                                        @endif
                                    </td>
                                    <td class="td2">
                                        @if ($a['working'] == '1')
                                            {{ 'Yes' }}
                                        @elseif ($a['working'] == '0')
                                            {{ 'No' }}
                                        @elseif ($a['working'] == '')
                                            {{ ' ' }}
                                        @endif
                                    </td>
                                    <td class="td2">{{ $a['comment'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                        <h5>Image: </h5>
                        @if (count($all['image']) > 0)
                            @foreach ($all['image'] as $img)
                                <img src="{{config('app.api_url_server')}}{{ $img }}" alt=""
                                    style="width:200px;height:160px">
                            @endforeach
                        @else
                            <p>No Image Found</p>
                        @endif
                        <br>
                    @endforeach
                @endforeach
                <br>
                <br>
                <table class="table1">
                    <tr class="td2">
                        <td style="height:40px"> </td>
                        <td style="height:40px"> </td>
                    </tr>
                    <tr class="td2">
                        <td style="text-align:center">Signature Manager</td>
                        <td style="text-align:center">Signature Tenant</td>
                    </tr>
                </table>
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
