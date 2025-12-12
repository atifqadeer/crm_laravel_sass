<!DOCTYPE html>
<html>
<head>
    <title>{{ $subject }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .email-header {
            background-color: #004b8d;
            padding: 20px;
            text-align: center;
        }
        .email-header img {
            max-height: 50px;
        }
        .email-title {
            color: #ffffff;
            font-size: 22px;
            margin-top: 10px;
        }
        .email-body {
            padding: 30px 20px;
            line-height: 1.8;
            font-size: 15px;
            color: #333;
        }
        .email-footer {
            padding: 15px 20px;
            font-size: 12px;
            color: #888;
            text-align: center;
            background-color: #f8f9fa;
        }
        @media only screen and (max-width: 600px) {
            .email-body, .email-footer {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                {{-- Replace with your company logo URL --}}
                {{-- <img src="{{ $base64Image }}" alt="Company Logo" style="max-width: 200px; height: auto;"> --}}
                <div class="email-title">{{ $subject }}</div>
            </div>
            <div class="email-body">
                {!! $template !!}
            </div>
            {{-- <div class="email-footer">
                &copy; {{ date('Y') }} {{ $from_name }}. All rights reserved.
            </div> --}}
        </div>
    </div>
</body>
</html>
