<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Courses Page</title>
    <link rel="stylesheet" href="{{ asset('assets/home/css/style.css') }}" />
    <style @nonce>
        body {

            /*            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;*/

            font-family: Arial, sans-serif;




        }



        header {
            text-align: center;
            margin: 20px;
        }

        .navbar {
            text-align: center;
            background-color: #016234;
            padding: 10px;
        }

        .navbar a {
            text-decoration: none;
            color: #fff;
            padding: 10px 20px;
            margin: 5px;
            background-color: #C5071C;
            border-radius: 5px;
            display: inline-block;
        }

        .navbar a:hover {
            background-color: #016234;
        }

        .courses {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
            padding: 0 20px;
        }

        .course-section {
            display: none;
        }

        .course-section.active {
            display: block;
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        /*.course-card {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
         box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: left;
        display: flex; /* Use flexbox to control card height */
        flex-direction: column;
        /* Stack content vertically */
        }

        */ .course-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: left;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.3s ease;
            /* Smooth transition for the shadow */
        }

        .course-image img {
            width: 100%;
            max-width: 50% auto;
            border-radius: 8px;
        }

        .course-content {
            flex-grow: 1;
            /* Allow content to grow and fill available space */
        }

        .course-title {
            color: #ee4606;
            font-size: 18px;
        }

        .course-button {
            background-color: #C5071C;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 50px 50px 50px 50px;
            margin-top: 10px;
        }

        .course-button:hover {
            background-color: #C5071C;


        }



        /* ... (previous CSS) ... */

        .course-description {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            /* Limit to 4 lines */
            -webkit-box-orient: vertical;
        }

        @media (max-width: 768px) {
            .course-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                /* Adjust card width for smaller screens */
            }
        }

        @media (max-width: 480px) {
            .navbar a {
                display: block;
                /* Stack navbar links on small screens */
                margin: 5px auto;
                /* Center links */
            }
        }

        /* ... (rest of the CSS) ... */
    </style>
</head>

<body>
    <header>
        <h1>One Million Coders Program (Pilot Courses)</h1>
    </header>

    <div class="navbar">
        <span class="menu-toggle" onclick="toggleMenu()"> </span>
        <div class="navbar-links" id="navbarLinks">
            <a href="javascript:void(0);" onclick="showCategory('all')">All Courses</a>
            <a href="javascript:void(0);" onclick="showCategory('cybersecurity')">Cybersecurity</a>
            <a href="javascript:void(0);" onclick="showCategory('data-protection')">Data Protection</a>
            <a href="javascript:void(0);" onclick="showCategory('ai')">Artificial Intelligence</a>
        </div>
    </div>

    <div class="courses">
        <section id="all" class="course-section active">
            <div class="course-grid">
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/cybersecurity.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Cybersecurity Professional</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'cybersecurity-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/cybersecurity.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Network Support Technician (CNST)</h2>
                        <p class="course-description">
                            Provide technical support with a focus on networking. Assist in network device management.
                        </p>
                        <a href="{{ route('dynamic-course', 'cnst-course') }}" class="course-button">Read More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/ai.jpeg') }}" alt="Data Protection Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Data Analyst Associate</h2>
                        <p class="course-description">
                            This course focuses on safeguarding sensitive and personal data in today's digital world. It
                            covers key topics such as data privacy laws (including GDPR), encryption techniques, risk
                            management, and best practices for mitigating data breaches.
                        </p>
                        <a href="{{ route('dynamic-course', 'ai-course') }}" class="course-button">Read More</a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Expert</h2>
                        <p class="course-description">
                            Advanced Data Protection Strategy & Policy Development, International Data Transfers &
                            Cross-Border Compliance, Privacy Program Management & Regulatory Engagement, Managing
                            Large-Scale Data Protection Programs, Incident Response & Data Breach Management.
                        </p>
                        <a href="{{ route('dynamic-course', 'protection-expert-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>

                <!-- <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/ai.jpeg') }} " alt="Artificial Intelligence Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Artificial Intelligence Training</h2>
                        <p class="course-description">
                            This course offers comprehensive, hands-on training in artificial intelligence fundamentals, including machine learning, neural networks, and deep learning. Designed for both beginners and those looking to expand their expertise, the curriculum covers data preprocessing, model building, algorithm selection, and the deployment of AI solutions.
                        </p>
                        <a href="{{ route('dynamic-course', 'ai-course') }}" class="course-button">Read More</a>
                    </div>
                </div> -->
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Manager</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'protection-sup-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Professional</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'data-protection-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Officer</h2>
                        <p class="course-description">
                            Monitor compliance with data protection laws and policies, detect and assess data protection
                            risks and breaches, respond to data incidents and ensure regulatory compliance.
                        </p>
                        <a href="{{ route('dynamic-course', 'certified-dpf-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="cybersecurity" class="course-section">
            <div class="course-grid">
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/cybersecurity.jpg') }}"
                            alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Cybersecurity Professional</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'cybersecurity-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/cybersecurity.jpg') }}"
                            alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Network Support Technician (CNST)</h2>
                        <p class="course-description">
                            Provide technical support with a focus on networking. Assist in network device management.
                        </p>
                        <a href="{{ route('dynamic-course', 'cnst-course') }}" class="course-button">Read More</a>
                    </div>
                </div>
            </div>

        </section>

        <section id="data-protection" class="course-section">
            <div class="course-grid">

                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Manager</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'protection-sup-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Expert</h2>
                        <p class="course-description">
                            Advanced Data Protection Strategy & Policy Development, International Data Transfers &
                            Cross-Border Compliance, Privacy Program Management & Regulatory Engagement, Managing
                            Large-Scale Data Protection Programs, Incident Response & Data Breach Management.
                        </p>
                        <a href="{{ route('dynamic-course', 'protection-expert-course') }}"
                            class="course-button">Read More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Data Protection Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Officer</h2>
                        <p class="course-description">
                            Monitor compliance with data protection laws and policies, detect and assess data protection
                            risks and breaches, respond to data incidents and ensure regulatory compliance.
                        </p>
                        <a href="{{ route('dynamic-course', 'certified-dpf-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/data.jpg') }}" alt="Cybersecurity Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Certified Data Protection Professional</h2>
                        <p class="course-description">
                            This course emphasizes hands-on practice, allowing you to apply your new skills to build
                            secure software, making it a perfect starting point for those looking to delve into both
                            programming and cybersecurity.
                        </p>
                        <a href="{{ route('dynamic-course', 'data-protection-course') }}" class="course-button">Read
                            More</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="ai" class="course-section">
            <div class="course-grid">
                <div class="course-card">
                    <div class="course-image">
                        <img src="{{ url('assets/home/images/ai.jpeg') }}"
                            alt="Artificial Intelligence Course Image" />
                    </div>
                    <div class="course-content">
                        <h2 class="course-title">Data Analyst Associate</h2>
                        <p class="course-description">
                            Analyze and preprocess data for models. Create visualizations using Microsoft Technologies
                        </p>
                        <a href="{{ route('dynamic-course', 'ai-course') }}" class="course-button">Read More</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script @nonce>
        function showCategory(category) {
            const sections = document.querySelectorAll('.course-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            if (category === 'all') {
                const allSection = document.getElementById('all');
                allSection.classList.add('active');
            } else {
                const selectedCategory = document.getElementById(category);
                selectedCategory.classList.add('active');
            }
        }

        showCategory('all');


        function toggleMenu() {
            var x = document.getElementById("navbarLinks");
            x.classList.toggle("show");
        }
    </script>
</body>

</html>
