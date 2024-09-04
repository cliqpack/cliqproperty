<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    
    <title>Document</title>
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
                width: 21cm;
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

    </style>

</head>

<body>
    <div class="page">
        <div style="margin-bottom: 2em">
            <div class="clearfix">
                <div style="float:left">
                    @if ($data[2]['brand_image'] !== null)
                      <img src="{{ getenv('API_IMAGE').$data[2]['brand_image'] ?? null}}" style="width: 100px;  object-fit: cover;" alt="Brand Image">
                    @endif

                    

                    {{-- <a href="https://mydaybucket.s3.ap-southeast-1.amazonaws.com/{{ $data[2]['brand_image'] }}" target="_blank">{{ $data[2]['brand_image'] }}</a> --}}
                    {{-- <img src="https://mydaybucket.s3.ap-southeast-1.amazonaws.com/{{ $data[2]['brand_image'] }}" alt="Brand Image"> --}}



                </div>
                <div style="float:right; text-align: right; width: calc(100% - 120px);"> <!-- Adjust the width accordingly -->
                    <div>
                        <strong>(w) {{ $data[3]['work_phone'] ? $data[3]['work_phone'] : '48527438590' }}</strong>
                        <br>
                        <div>
                            {{ $data[3]['email'] ? $data[3]['email'] : 'info@cliqproperty.com' }}
                        </div>
                        {{$data[0]["job_number"]}}<br>
                        <div>
                            {{ $data[3]['address'] ? $data[3]['address'] : 'Melbourne' }}
                        </div>
                        <div>
                            ABN:85 654 549 014
                        </div>
                        <div>
                            Licence:{{ $data[4]['licence_number'] ? $data[4]['licence_number'] : '088702L' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <span style="float:left">
                    <br />
                    <h2 style="color:{{ $data[1]["primary_colour"] ?? '#153D58'}}">Work Order(工作订单)</h2>
                    {{ $data[0]["supplier_company_name"] }} <br />
                    {{ $data[0]["supplier_name"] }} <br />
                    {{$data[0]["supplier_mobile_phone"]}}<br/>
                    {{ $data[0]["supplier_email"] }}
                </span>

                <div style="float:right; text-align: right; ">
                    <br />
                    <br />
                    <span style="color:{{ $data[1]["primary_colour"] ?? '#153D58'}}; font-weight: bold; font-size: 1.5rem;">Job number(工作号码) - {{ $data[0]["job_number"] }}</span>
                    <br />
                    <br />

                    Created on: {{ $data[0]["created_at"] }} <br />
                    Due(截止日期): {{ $data[0]["due_by"] }}
                </div>
            </div>
        </div>
        <hr>
        <div class="clearfix" style="margin-bottom: 20px;">
            <div style="width: 40%;">
                <span style="float:left">
                    <br />
                    <h2 style="color:{{ $data[1]["primary_colour"] ?? '#153D58'}}">Details(细节)</h2>
                    <p>Property(物业)</p>
                    <p>&nbsp;&nbsp;&nbsp;{{ $data[0]["property_reference"] }}</p>
                    <span>For access contact the tenant on(需要进入请联系租户):</span>
                    <p>&nbsp;&nbsp;&nbsp;{{ $data[0]["tenant_name"] }}</p>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(m){{ $data[0]["tenant_mobile_phone"] }}&nbsp;(w){{ $data[0]["tenant_home_phone"] }}&nbsp;(h){{ $data[0]["tenant_work_phone"] }}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(e){{ $data[0]["tenant_email"] }}</span>
                    <p>Work order issued on behalf of the owner(代表业主发布工作订单) - {{ $data[0]["owner_name"] }}</p>
                </span>
            </div>

            <div style="float:right; text-align: right; ">
                <div>
                    <br /><br /><br /><br />
                    <p>For queries contact the agent on (有疑问请联系代理人):</p>
                    <span>&nbsp;&nbsp;&nbsp;{{$data[0]["managerName"]}}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;(m){{ $data[0]["agent_mobile_phone"]}}</span><br>
                    <span>&nbsp;&nbsp;&nbsp;(e){{  $data[0]["agent_email"] }}</span><br>
                </div>
            </div>
        </div>
        <hr>
        <div>
            <h2 style="color:{{ $data[1]["primary_colour"] ?? '#153D58'}}">Description(描述)</h2>
            <span>&nbsp;&nbsp;Summary(摘要)</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$data[0]["summary"]}}</span><br><br>
            <span>&nbsp;&nbsp;Description(描述)</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$data[0]["description"]}}</span><br><br>
            <span>&nbsp;&nbsp;Note(注释)</span><br><br>
            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$data[0]["work_order_notes"]}}</span>
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
