<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>One Million Coders Journey</title>
    <style @nonce>
        /* Reset & Basic Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            color: #333;
            line-height: 1.6;
            padding: 1rem;

        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Container */
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #007bff, #ff0000);
            color: #fff;
            padding: 2rem 1rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Steps Section */
        .steps {
            padding: 2rem 1.5rem;
        }

        .steps h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
            color: #007bff;
        }

        .step-list {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 1.5rem;
        }

        .step-item {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            flex: 1 1 250px;
            text-align: center;
        }

        .step-item h3 {
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
            color: #333;
        }

        .step-item p {
            font-size: 0.95rem;
            color: #666;
        }

        /* Info Note */
        .info-note {
            background: #e9f7ef;
            border-left: 4px solid #28a745;
            padding: 1rem;
            margin: 2rem 1rem;
            font-size: 1rem;
            color: #333;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .hero h1 {
                font-size: 1.5rem;
            }

            .hero p {
                font-size: 0.9rem;
            }

            .steps h2 {
                font-size: 1.3rem;
            }
        }

        .goto-home {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 6px;
        }

        .homepage-link {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #007bff, #ff0000);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .homepage-link:hover {
            background: linear-gradient(135deg, #ff0000, #007bff);
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Your journey to join the One Million Coders Program is underway!</h1>

            <p>
                We’re excited to guide you through your next steps.
            </p>
        </section>

        <!-- Steps Section -->
        <section class="steps">
            <h2>Your Next Steps</h2>
            <ul class="step-list">
                <li class="step-item">
                    <h3>Aptitude Test</h3>
                    <p>Prepare and complete the online aptitude test to assess your skills.</p>
                </li>
                <li class="step-item">
                    <h3>Shortlisting</h3>
                    <p>We will review test results and shortlist candidates for final admission.</p>
                </li>
                <li class="step-item">
                    <h3>Final Admission</h3>
                    <p>Receive confirmation of your admission into the program.</p>
                </li>
            </ul>
        </section>

        <!-- Info Note -->
        <div class="info-note">
            Please check your email and SMS for test dates, shortlist announcements, and admission updates to stay
            informed.
        </div>
    </div>
    <div class="goto-home">
        <a class="homepage-link" target="_parent" href="{{ url('/') }}">Go To Homepage</a>
    </div>

</body>

</html>
