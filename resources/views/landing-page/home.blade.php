<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Courses Page</title>
    <link rel="stylesheet" href="{{ asset('assets/home/css/style.css') }}" />
    <style>
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


        .course-image{
            width: 100%;
            height: 250px
        }

        .course-image img {
            width: 100%;
            height: 100%;
            max-width: 50% auto;
            border-radius: 8px;
            object-fit: cover;
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

        .text-capitalize {
            text-transform: capitalize !important;
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

            /* ... (rest of the CSS) ... */
    </style>
</head>


<body>
    <header>
        <h1>One Million Coders Program (Pilot Courses)</h1>
    </header>

        <div class="navbar">
            <span class="menu-toggle" onclick="toggleMenu()"> </span>
            <div class="navbar-links  text-capitalize" id="navbarLinks">
                <a href="javascript:void(0);" onclick="showCategory('all')">All Courses</a>
                @foreach($categories as $category)
                <a href="javascript:void(0);" onclick="showCategory('{{ $category->slug }}')">{{ $category->title }}</a>
                @endforeach
            </div>
        </div>

        <div class="courses">
            <section id="all" class="course-section active">
                <div class="course-grid">
                    @foreach ($programmes as $programme)
                    <div class="course-card">
                        <div class="course-image">
                            <img src="{{ asset('storage/programme/' . $programme->image) }}" alt="{{ $programme->title }} Course Image" />
                        </div>
                        <div class="course-content">
                            <h2 class="course-title">{{ $programme->title }}</h2>
                            <p class="course-description">
                                {{ $programme->description }}
                            </p>
                            <a href="{{ route('course', $programme->slug) }}" class="course-button">Read More</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>


            @foreach($categories as $category)
            <section id="{{ $category->slug }}" class="course-section">

                <div class="course-grid">
                    @foreach ($category->programmes as $programme)
                    <div class="course-card">
                        <div class="course-image">
                            <img src="{{ asset('storage/programme/' . $programme->image) }}" alt="{{ $programme->title }} Course Image" />
                        </div>
                        <div class="course-content">
                            <h2 class="course-title">{{ $programme->title }}</h2>
                            <p class="course-description">
                                {{ $programme->description }}
                            </p>
                            <a href="{{ route('course', $programme->slug) }}" class="course-button">Read More</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
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
