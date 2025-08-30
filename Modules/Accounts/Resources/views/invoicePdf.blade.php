<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>
</head>
<body>
    <div class="document-container">

        <div class="header-section">
            <div class="logo-section">
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
            <div class="company-info">
                <div class="contact-item"><strong>Phone:</strong> {{ $company->phone ?? '703-701-9964' }}</div>
                <div class="contact-item"><strong>Web:</strong> {{ $company->website ?? 'app.cliqproperty.com' }}</div>
                <div class="contact-item"><strong>Email:</strong> {{ $company->email ?? 'info@cliqproperty.com' }}</div>
                <div class="address-item">{{ $company->address ?? '123 Business Street, City, State 12345' }}</div>
                <div class="business-item"><strong>ABN:</strong> {{ $company->abn ?? '12 443 000 000' }}</div>
                <div class="business-item"><strong>Licence:</strong> {{ $company->license ?? '78632372' }}</div>
            </div>
        </div>

        <div class="address2">
            <div class="clearfix" style="margin-bottom: 20px;">
                <div style="float:left;">
                    <h3>Tenant: {{ $tenant_name }}</h3>
                </div>
                <div style="float:right; text-align:right; margin-right: 40px;">
                    <span style="font-weight: bold; font-size: 1.5rem;">Tax Invoice</span><br><br>
                    <strong>Folio:</strong> {{ $tenant_folio }}<br>
                    <strong>Created date:</strong> {{ $created_date }}<br>
                    <strong>Invoice #:</strong> 000{{ $invoice_id }}
                </div>
            </div>

            <table class="table1">
                <tr>
                    <th class="th1" style="text-align: center;">Property</th>
                    <th class="th1">Due date</th>
                    <th class="th1">Due</th>
                </tr>
                <tr>
                    <td class="td1">{{ $propAddress }}</td>
                    <td class="td1">{{ $due_date }}</td>
                    <td class="td1">${{ $amount }}</td>
                </tr>
            </table>
        </div>

        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left;"><strong>Description</strong></div>
            <div style="float:right; width: 100px; text-align: right;">Amount</div>
            <div style="float:right; width: 200px; text-align: right;">Include Tax</div>
        </div>
        <hr>

        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:left;">{{ $description }}</div>
            <div style="float:right; width: 100px; text-align: right;">${{ $amount }}</div>
            <div style="float:right; width: 200px; text-align: right;">${{ $taxAmount }}</div>
        </div>
        <hr>

        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:right; width: 100px; text-align: right;">${{ $paid }}</div>
            <div style="float:right; width: 350px; text-align: right;">Amount Paid:</div>
        </div>
        <hr>

        <div class="clearfix" style="margin-top: 20px;">
            <div style="float:right; width: 100px; text-align: right;">${{ $dueAmount }}</div>
            <div style="float:right; width: 350px; text-align: right;">Amount Due:</div>
        </div>
        <hr>
    </div>
</body>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 13px;
        line-height: 1.6;
        color: #333;
        background-color: #f9f9f9;
    }

    .document-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 30px;
        background-color: #fff;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .logo-section img {
        max-width: 180px;
        max-height: 80px;
        object-fit: contain;
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

    .company-info {
        text-align: right;
        font-size: 12px;
    }

    .company-info .contact-item,
    .company-info .address-item,
    .company-info .business-item {
        margin-bottom: 4px;
    }

    .address2 {
        width: 100%;
        margin-bottom: 50px;
    }

    .table1 {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .th1, .td1 {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }

    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
</style>
</html>
