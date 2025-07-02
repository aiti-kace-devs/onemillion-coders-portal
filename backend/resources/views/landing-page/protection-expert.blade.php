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


        /*register-button {
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
                <h1> <span class="detail-label">Certified Data Protection Expert (CDPE) – Expert Level</span></h1>
                <!-- <span class="detail-value">Cybersecurity Specialist</span> -->
            </div>
            <div class="detail-item">
                <span class="detail-label">Objective:</span>
                <span class="detail-value">Recognize and validate mastery in strategic data protection management,
                    regulatory engagement, and cross-border compliance through demonstrated real-world experience and
                    industry contribution.</span>
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
                <span class="detail-label">Qualification Criteria:</span>
                <span class="detail-value">Candidates must demonstrate expertise through a combination of the
                    following:</span>
                <ul class="detail-value">
                    <li><b>Proof of Practice:</b> Documented experience managing complex data protection programs or
                        initiatives.</li>
                    <li><b>Board Assessment:</b> Evaluation by an expert panel based on submitted work, achievements,
                        and impact.</li>
                    <li><b>Continuous Professional Development (CPD):</b> Participation in CPD activities with points
                        accrued over time.</li>
                    <li><b>Publications:</b> Authorship of articles, research papers, or thought leadership pieces in
                        reputable journals or platforms</li>
                    <li><b>Industry Engagement:</b> Active participation in data protection conferences, forums, and
                        advisory roles.</li>
                    <li><b>Sector-Specific Training & Contributions:</b> Delivery or participation in specialized
                        trainings within industries such as health, finance, education, telecommunications, etc.</li>
                </ul>
            </div>
            <div class="detail-item">
                <span class="detail-label">Training Duration:</span>
                <span class="detail-value">1 week</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Outcome:</span>
                <span class="detail-value">Award of CDPE Certification, enabling professionals to:</span>
                <ul class="detail-value">
                    <li>Offer Data Protection as a Service <b>(DPaaS)</b></li>
                    <li>Practice across Africa under mutual recognition and reciprocal arrangements</li>
                </ul>
            </div>
            <a href="{{ url('/forms/register') }}" class="register-button">Register</a>
        </div>
        <div class="image-content">
            <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection">
        </div>
    </div>
</body>

</html>
