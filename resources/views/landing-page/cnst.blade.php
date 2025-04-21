<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cybersecurity Course Details</title>
    <link rel="stylesheet" href="{{ asset('assets/home/css/style.css') }}">
    <style @nonce>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e8f0fe;
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

        .detail-label {
            font-weight: bold;
            display: block;
        }

        /* .register-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        } */
    </style>
</head>

<body>
    <div class="container">
        <div class="text-content">
            <div class="detail-item">
                <h1> <span class="detail-label">Certified Network Support Technician (CNST)</span></h1>
                <!-- <span class="detail-value">Certified Cybersecurity Professional</span> -->
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Modules:</span>
                <ul class="detail-value">

                    <li>Networking Basics</li>
                    <li>Networking Devices and Initial Configuration</li>
                    <li>Network Addressing and Basic Troubleshooting</li>
                    <li>Network Support and Security</li>

                </ul>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Duration:</span>
                <span class="detail-value">4 weeks</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Prerequisite:</span>
                <span class="detail-value">Basic computer literacy</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Available Certifications:</span>
                <span class="detail-value">Cisco Certified Support Technician (CCST) Networking</span>
            </div>
            <a href="{{ url('/forms/register') }}" class="register-button">Register</a>
        </div>
        <div class="image-content">
            <img src="{{ url('assets/home/images/cybersecurity.jpg') }}" alt="Cybersecurity Course Image">
        </div>
    </div>
</body>

</html>
