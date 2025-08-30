<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tax Invoice</title>
</head>

<body>
    <div style="margin-bottom: 2em;">
        <div class="clearfix">
            <!-- Logo Section -->
            <div style="float: left;">
                @if(isset($logoData) && $logoData)
                    <img src="{{ $logoData }}" alt="Company Logo" />
                @elseif(isset($brandLogo->brand_image) && $brandLogo->brand_image)
                    @php
                        $logoUrl = Storage::disk('s3')->url($brandLogo->brand_image);
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Company Logo" />
                @else
                    @php
                        $defaultLogoPath = public_path('/dist/img/Asset5.png');
                        $defaultLogoData = null;
                        if (file_exists($defaultLogoPath)) {
                            $defaultImageContent = file_get_contents($defaultLogoPath);
                            $defaultMimeType = mime_content_type($defaultLogoPath);
                            $defaultLogoData = 'data:' . $defaultMimeType . ';base64,' . base64_encode($defaultImageContent);
                        }
                    @endphp
                    @if($defaultLogoData)
                        <img src="{{ $defaultLogoData }}" style="width:200px;height:60px;object-fit:contain;" alt="Default Logo" />
                    @else
                        <div class="default-logo">Logo</div>
                    @endif
                @endif
            </div>

            <!-- Company Info -->
            <div style="float: right; width: 200px;">
                <div class="contact-item"><strong>Phone:</strong> {{ $company->phone ?? '703-701-9964' }}</div>
                <div class="contact-item"><strong>Web:</strong> {{ $company->website ?? 'app.cliqproperty.com' }}</div>
                <div class="contact-item"><strong>Email:</strong> {{ $company->email ?? 'info@cliqproperty.com' }}</div>
                <div class="address-item">{{ $propAddress ?? '123 Business Street, City, State 12345' }}</div>
                <div class="business-item"><strong>ABN:</strong> {{ $company->abn ?? '12 443 000 000' }}</div>
                <div class="business-item"><strong>Licence:</strong> {{ $company->license ?? '78632372' }}</div>
            </div>
        </div>
    </div>

    <!-- Owner & Invoice Details -->
    <div class="address2">
        <div class="clearfix" style="margin-bottom: 20px;">
            <div style="float: left;">
                <h3>Owner: {{ $owner_name }}</h3>
            </div>
            <div style="float: right; margin-right: 40px;">
                <span style="font-weight: bold; font-size: 1.5rem;">Tax Invoice</span><br><br>
                Folio: {{ $owner_folio }}<br />
                Created date: {{ $created_date }}<br />
                Bill#: 000{{ $bill_id }}
            </div>
        </div>

        <!-- Property Info Table -->
        <div>
            <table class="table1">
                <tr>
                    <th class="th1" style="text-align: center;">Property</th>
                    <th class="th1">Due Date</th>
                    <th class="th1">Due</th>
                </tr>
                <tr>
                    <td class="td1">{{ $propAddress }}</td>
                    <td class="td1">{{ $due_date }}</td>
                    <td class="td1">${{ $amount }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Description Section -->
    <div class="clearfix" style="margin-top: 20px;">
        <div style="float: left;"><b>Description</b></div>
        <div style="float: right; width: 100px;">Amount</div>
        <div style="float: right; width: 200px;">Include Tax</div>
    </div>
    <hr>
    <div class="clearfix" style="margin-top: 20px;">
        <div style="float: left;">{{ $description }}</div>
        <div style="float: right; width: 100px;">${{ $amount }}</div>
        <div style="float: right; width: 200px;">${{ $taxAmount }}</div>
    </div>
    <hr>
</body>

<style>
    body {
        background-color: #ffffff;
        font-family: Arial, sans-serif;
        font-size: 13px;
        color: #000;
    }

    .table1 {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
    }

    .th1, .td1 {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }

    .default-logo {
        width: 200px;
        height: 60px;
        border: 2px dashed #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #999;
        font-size: 12px;
    }

    .address2 {
        width: 100%;
        margin-bottom: 50px;
    }

    .contact-item, .address-item, .business-item {
        margin-bottom: 5px;
    }
</style>

</html>
