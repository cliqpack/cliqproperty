
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Trust Account Receipt</title>
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Header */
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
            height: auto;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .default-logo {
            width: 200px;
            height: 60px;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
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

        /* Title */
        .document-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .document-title h1 {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            color: #000;
        }

        /* Receipt Info */
        .receipt-info-section {
            margin-bottom: 30px;
        }

        .property-info {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            border-bottom: 1px dashed #ccc;
            padding: 4px 0;
        }

        .label {
            font-weight: 600;
        }

        .value {
            font-weight: 400;
        }

        /* Table */
        .table-section {
            margin-bottom: 30px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .receipt-table th,
        .receipt-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
        }
.receipt-table th.tax-col,
.receipt-table th.amount-col,
.receipt-table td.tax-col,
.receipt-table td.amount-col,
.receipt-table tfoot .total-tax,
.receipt-table tfoot .total-amount {
    text-align: right;
    min-width: 100px;
    white-space: nowrap;
}

        .receipt-table th {
            text-align: left;
            background-color: #f1f1f1;
            text-transform: uppercase;
            font-size: 12px;
            color: #333;
        }

        .tax-col,
        .amount-col {
            text-align: right;
        }

        .receipt-table tfoot td {
            font-weight: bold;
            font-size: 14px;
            background-color: #f9f9f9;
        }

        .receipt-table tfoot .total-tax,
        .receipt-table tfoot .total-amount {
            text-align: right;
        }

        /* Footer */
        .footer-section {
            margin-top: 40px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .footer-item {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            border-bottom: 1px dotted #ccc;
            padding: 4px 0;
        }

        .footer-label {
            font-weight: 600;
        }

        .footer-value {
            font-weight: 400;
        }

        /* Brand Statement */
        .brand-statement {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-style: italic;
            font-size: 12px;
            text-align: center;
            color: #666;
        }

        @media print {
            .document-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
                max-width: none;
            }

            body {
                background-color: #fff;
            }
        }

        @media (max-width: 600px) {
            .header-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .company-info {
                text-align: center;
                margin-top: 10px;
            }

            .info-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="document-container">
        <!-- Header -->
        <div class="header-section">
            <div class="logo-section">
            @if(isset($logoData) && $logoData)
                {{-- Use base64 encoded image for PDF --}}
                <img src="{{ $logoData }}"  alt="Company Logo" />
            @elseif(isset($brandLogo->brand_image) && $brandLogo->brand_image)
                {{-- Fallback: try direct S3 URL --}}
                @php
                    $logoUrl = Storage::disk('s3')->url($brandLogo->brand_image);
                @endphp
                <img src="{{ $logoUrl }}"  alt="Company Logo" />
            @else
                {{-- Default logo --}}
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
                    <div style="width:200px;height:60px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;background-color:#f5f5f5;">
                        <span style="font-size:12px;color:#666;">Logo</span>
                    </div>
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

        <!-- Title -->
        <div class="document-title">
            <h1>Trust Account Receipt</h1>
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info-section">
            @if ($property_address->value)
                <div class="property-info">
                    <strong>For Property:</strong> {{ $property_address->value }}
                </div>
            @endif
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Receipt Number:</span>
                    <span class="value">{{ $receiptInformation->receiptNumber ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Date Received:</span>
                    <span class="value">{{ $receiptInformation->receiptDate ?? date('Y-m-d') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">On Behalf Of:</span>
                    <span class="value">{{ $receiptInformation->onBehalfOf ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ $receiptInformation->folioInfo ?? 'Folio' }}:</span>
                    <span class="value">{{ $receiptInformation->folioName ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Receipt Table -->
        <div class="table-section">
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th class="description-col">Description</th>
                        <th class="tax-col">Included Tax</th>
                        <th class="amount-col">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($receiptDetails))
                        @foreach ($receiptDetails as $details)
                            <tr>
                                <td>{{ $details['description'] ?? 'N/A' }}</td>
                                <td class="tax-col">${{ number_format($details['taxAmount'] ?? 0, 2) }}</td>
                                <td class="amount-col">${{ number_format($details['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3">No items found</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="total-tax"><strong>${{ number_format($receiptData->totalTaxAmount ?? 0, 2) }}</strong></td>
                        <td class="total-amount"><strong>${{ number_format($receiptData->totalAmount ?? 0, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <div class="footer-grid">
                <div class="footer-item">
                    <span class="footer-label">Payment Method:</span>
                    <span class="footer-value">{{ $footerDetails->payment_method ?? 'N/A' }}</span>
                </div>
                <div class="footer-item">
                    <span class="footer-label">Principal:</span>
                    <span class="footer-value">{{ $footerDetails->principal ?? 'N/A' }}</span>
                </div>
                <div class="footer-item">
                    <span class="footer-label">Company:</span>
                    <span class="footer-value">{{ $footerDetails->company ?? 'Company Name' }}</span>
                </div>
                <div class="footer-item">
                    <span class="footer-label">Receipted By:</span>
                    <span class="footer-value">{{ $footerDetails->receipted_by ?? 'N/A' }}</span>
                </div>
                <div class="footer-item">
                    <span class="footer-label">Date Processed:</span>
                    <span class="footer-value">{{ $receiptInformation->receiptDate ?? date('Y-m-d') }}</span>
                </div>
            </div>
        </div>

        <!-- Brand Statement -->
        @if($brandStatement && $brandStatement->statement)
            <div class="brand-statement">
                {{ $brandStatement->statement }}
            </div>
        @endif
    </div>
</body>

</html>
