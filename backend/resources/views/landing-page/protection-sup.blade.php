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
            background-color: #f4f4f4;
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

        /*  .register-button {
      display: inline-block;
      padding: 10px 20px;
      background-color: #007BFF;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin-top: 15px;
    }*/
    </style>
</head>

<body>
    <div class="container">
        <div class="text-content">
            <div class="detail-item">
                <h1> <span class="detail-label">Certified Data Protection Manager (CDPM ) – Advance Level</span></h1>
                <!-- <span class="detail-value">Cybersecurity Specialist</span> -->
            </div>
            <div class="detail-item">
                <span class="detail-label">Objective:</span>
                <span class="detail-value">Develop practical skills in data governance, risk management, and compliance
                    enforcement.</span>
            </div>
            <!-- <div class="detail-item">
        <span class="detail-label">No. of People to Train:</span>
        <span class="detail-value">20-30 participants per session</span>
      </div> -->
            <!-- <div class="detail-item">
        <span class="detail-label">Training Program:</span>
        <span class="detail-value">Certified Cybersecurity Professional</span>
      </div> -->
            <div class="detail-item">
                <span class="detail-label">Core Modules</span>
                <ul class="detail-value">
                    <li>Privacy by Design & Data Protection Impact Assessments (DPIA)</li>
                    <li>Managing Data Breaches & Incident Response Plans</li>
                    <li>Data Protection Internal Audits & Compliance Reporting</li>
                    <li>Role of the CDPM in Organizational Compliance</li>
                </ul>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Description:</span>
                <span class="detail-value">5-day intensive course delivered by DPC-accredited institutions.</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Assessment:</span>
                <span class="detail-value">TBD by the Commission Case study analysis.</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Outcome:</span>
                <span class="detail-value"><b>CDPM Certification</b></span>
            </div>
            <a href="{{ url('/forms/register') }}" class="register-button">Register</a>
        </div>
        <div class="image-content">
            <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection">
        </div>
    </div>
</body>

</html>
