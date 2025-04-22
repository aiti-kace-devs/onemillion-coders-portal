<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Protection Course Details</title>
    <link rel="stylesheet" href="{{ asset('assets/home/css/style.css') }}">
    <style @nonce>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            align-items: center;
            height: 100vh;
            padding: 20px;
            gap: 20px;
        }

        .text-content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .image-content img {
            width: 100%;
            height: 100vh;
            object-fit: cover;
            border-radius: 10px;
        }

        .detail-item {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="text-content">
            <div class="detail-item">
                <h1> <span class="detail-label">Data Analyst Associate</span></h1>
                <!-- <span class="detail-value">Data Analyst Associate</span> -->
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Modules:</span>
                <ul class="detail-value">
                    <li>Power BI Basics - Interface, data import, simple visuals</li>
                    <li>Data Cleaning (Power Query) - Transformations, merging, error handling</li>
                    <li>Data Modeling & DAX - Star schema, relationships, basic DAX</li>
                    <li>Advanced Visuals - Custom visuals, interactivity, drill-through</li>
                    <li>Power BI Service - Publishing, sharing, RLS, refreshes</li>
                    <li>Optimization - Query folding, aggregations, performance</li>
                </ul>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Duration:</span>
                <span class="detail-value">5 weeks</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Prerequisite:</span>
                <span class="detail-value">SHS graduate with Basic IT knowledge</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Available Certifications:</span>
                <span class="detail-value">Microsoft PL-300: Power BI Data Analyst Associate</span>
            </div>
            <a href="{{ url('/forms/register') }}" class="register-button">Register</a>
        </div>
        <div class="image-content">
            <img src="{{ url('assets/home/images/ai.jpeg') }}" alt="AI">
        </div>
    </div>
</body>

</html>
