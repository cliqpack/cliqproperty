<!-- resources/views/letterPdf.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <title>Letter PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .content {
            margin: 0 20px;
        }
    </style>
</head>

<body>
    <div class="content">
        {!! $letter->body !!}
    </div>
</body>

</html>