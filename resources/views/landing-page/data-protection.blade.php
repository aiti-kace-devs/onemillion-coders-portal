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

        /* .register-button {
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
                <h1> <span class="detail-label">Certified Data Protection Professional (CDPP) – Practitioner Level</span>
                </h1>
                <!-- <span class="detail-value">Cybersecurity Specialist</span> -->
            </div>
            <div class="detail-item">
                <span class="detail-label">Objective:</span>
                <span class="detail-value">Equip professionals with advanced practical skills to implement and manage
                    data protection frameworks, conduct audits, and lead compliance initiatives.</span>
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
                <span class="detail-label">Core Modules:</span>
                <ul class="detail-value">
                    <li><b>Advanced Compliance Auditing & Enforcement</b></li>
                    <ul class="detail-value">
                        <li>Techniques for conducting internal/external audits.</li>
                        <li>Remediation strategies for non-compliance.</li>
                    </ul>

                    <li><b>Operationalizing Privacy by Design</b></li>
                    <ul class="detail-value">
                        <li>Integrating data protection into business processes.</li>
                        <li>Tools for implementing PbD in IT systems and workflows.</li>
                    </ul>

                    <li><b>Cross-Border Data Transfers & Legal Mechanisms GDPR.</b></li>
                    <ul class="detail-value">
                        <li><b>International Compliance Mechanisms</b></li>
                        <ul class="detail-value">
                            <li><b>Standard Contractual Clauses (SCCs):</b></li>
                            <ul class="detail-value">
                                <li>Use cases, structure, and implementation requirements</li>
                                <li>Updates under the EU GDPR and implications for African data exporters.</li>
                            </ul>

                            <li><b>Binding Corporate Rules (BCRs):</b></li>
                            <ul class="detail-value">
                                <li>Overview and approval process.</li>
                                <li>Applicability for multinational organizations operating across jurisdictions.</li>
                            </ul>

                            <li><b>Adequacy Decisions:</b></li>
                            <ul class="detail-value">
                                <li>Concept and criteria under the GDPR.</li>
                                <li>Assessment of adequacy and its significance for third countries, including possible
                                    future recognition of African states.</li>
                            </ul>

                            <li><b>Other Mechanisms:</b></li>
                            <ul class="detail-value">
                                <li>Codes of Conduct and Certification Schemes.</li>
                                <li>Derogations for specific situations (e.g., explicit consent, contract performance).
                                </li>
                            </ul>
                        </ul>
                    </ul>

                    <li><b>Stakeholder Engagement & Training</b></li>
                    <ul class="detail-value">
                        <li>Developing internal training programs.</li>
                        <li>Communicating data protection requirements to non-technical teams.</li>
                    </ul>
                </ul>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Duration:</span>
                <span class="detail-value">5 weeks</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Assessment:</span>
                <span class="detail-value">Research project, professional presentation and oral presentation.</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Outcome:</span>
                <span class="detail-value"><b>CDPP Certification</b></span>
            </div>

            <a href="{{ url('/forms/register') }}" class="register-button">Register</a>
        </div>
        <div class="image-content">
            <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection">
        </div>
    </div>
</body>

</html>
