<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    {{-- <meta name="viewport" content="width=device-width, initial-scale=0.5"> --}}
    {{-- <meta http-equiv="X-UA-Compatible" content="ie=edge"> --}}
    {{-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> --}}
    <title>Document(文件)</title>
    {{-- <link rel="stylesheet" href="stylepdf.css"> --}}

</head>
<style>
    body {
        background-color: #eeeeee;
        margin: 0;
        padding: 0;
        font-family: 'Arial', sans-serif;
    }

    .page {
        background-color: #ffffff;
        width: 21cm;
        height: 29.7cm;
        margin: 1cm auto;
        padding: 1cm;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }

    .clearfix {
        page-break-inside: avoid;
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

    .address2 {
        width: 100%;
        margin-bottom: 50px;
    }

    .balance {
        display: flex;
        justify-content: space-between
    }

    @media print {
        body {
            font-size: 12pt;
        }

        .page {
            width: 18cm;
            height: 29.7cm;
            margin: 1cm auto;
            padding: 1cm;
            box-sizing: border-box;
            page-break-after: always;
        }

        .clearfix {
            page-break-inside: avoid;
        }

        .add,
        .balance {
            display: none;
        }
    }

    img {
        max-width: 100%;
        height: auto;
    }

    .right-content {
        display: flex;
        justify-content: flex-end;
        text-align: right;
        width: 80%; /* Adjust the width as needed */
    }
</style>

<body>
    <div class="page">
        <div style="margin-bottom: 2em">
            <div class="clearfix">
                <div style="float:left">
                    <img src="getenv('API_IMAGE').{{ $brandLogo['brand_image'] ?? null }}" style="width: 100px;  object-fit: cover;" alt="Brand Image">
                </div>
                <div class="right-content">
                    <div>
                        <strong>(w) {{ $user['work_phone'] ? $user['work_phone'] : '1112222333' }}</strong>
                        <br>
                        <div>
                            {{ $user['email'] ? $user['email'] : 'info@cliqproperty.com' }}
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
            <h4 class="text-center">Routine Report(常规报告)</h4>
            <br><br>
            @if (count($data) > 0)
                @foreach ($data as $d)
                    <br>
                    <strong>{{ $d['room'] }}</strong>
                    <p>Description(描述): {{ $d['routine_description'] }}</p>
                    {{-- <p>{{$d["image"]}}</p> --}}
                    {{-- @foreach ($d['image'] as $img)
                <img src="{{ public_path('/public/image/')}}/{{$img}}" alt="" style="width:200px;height:60px">
            @endforeach --}}

                    <h5>Image(图片): </h5>
                    @if (count($d['image']) > 0)
                        @foreach ($d['image'] as $img)
                            <img src="{{config('app.api_url_server')}}{{ $img }}" alt=""
                                style="width:200px;height:160px">
                        @endforeach
                    @else
                        <p>No Image Found(未找到图像)</p>
                    @endif
                @endForeach
            @else
                <h5>未找到数据！这份报告是新的。请插入报告数据。</h5>
            @endif

        </div>


    </div>
    </div>

</body>

</html>
