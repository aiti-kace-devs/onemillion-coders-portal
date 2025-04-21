@extends('layouts.app', [
    'activePage' => 'dashboard',
])
@section('title', 'Dashboard')
@section('content')
    <style @nonce>
        .chart-height {
            height: 294px;
        }
    </style>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Admin</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Admin</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $student }}</h3>

                                <p>Total students</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->

                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $shortlist }}</h3>

                                <p>Shortlisted Students</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                        </div>
                    </div>



                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $admission }}</h3>

                                <p>Total admissions</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>

                            </div>
                        </div>
                    </div>
                    <!-- ./col -->

                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $course }}</h3>
                                <p>Total Courses</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-ios-book"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <!-- ./col -->
                </div>
                <!-- /.content -->
            </div>
            <!-- /.content-wrapper -->
            <!-- /.row -->

            <!-- Chart dashboard -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Registrations per Day -->
                    <div class="card mb-3">
                        <div class="card-header p-2">
                            <h3 class="card-title">Registrations per Day</h3>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-height">
                                <canvas id="registrationsPerDayChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Students per Region -->
                    <div class="card mb-3">
                        <div class="card-header p-2">
                            <h3 class="card-title">Students per Region</h3>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-height">
                                <canvas id="studentsPerRegionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Age Groups Chart -->
                    <div class="card">
                        <div class="card-header p-2">
                            <h3 class="card-title">Age Group Distribution</h3>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-height">
                                <canvas id="ageGroupsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Gender Distribution -->
                    <div class="card mb-3">
                        <div class="card-header p-2">
                            <h3 class="card-title">Gender Distribution</h3>
                        </div>
                        <div class="card-body p-2">
                            {{-- <div style="height: 180px; display: flex; align-items: center; justify-content: center;"> --}}
                            <div class="chart-height">
                                <canvas id="genderDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Students per Course -->
                    <div class="card">
                        <div class="card-header p-2">
                            <h3 class="card-title">Students per Course</h3>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-height">
                                <canvas id="studentsPerCourseChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header p-2">
                            <h3 class="card-title"> Admitted Students per Region</h3>
                        </div>
                        <div class="card-body p-2">
                            <div class="chart-height">
                                <canvas id="admitedstudentsPerRegionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </section>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Chart.js -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}
    <script src="{{ url('/assets/plugin/chart.js/Chart.min.js') }}" referrerpolicy="no-referrer"></script>

    <script @nonce>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate random colors for charts
            const generateColors = (count) => {
                const colors = [];
                for (let i = 0; i < count; i++) {
                    const r = Math.floor(Math.random() * 200) + 50;
                    const g = Math.floor(Math.random() * 200) + 50;
                    const b = Math.floor(Math.random() * 200) + 50;
                    colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
                }
                return colors;
            };

            // Common chart options
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            }
                        }
                    }
                }
            };

            // Students per Region Chart
            const regionCtx = document.getElementById('studentsPerRegionChart').getContext('2d');
            const regions = {!! json_encode($studentsPerRegion->pluck('region')) !!};
            const counts = {!! json_encode($studentsPerRegion->pluck('total')) !!};
            const backgroundColors = generateColors(regions.length);

            new Chart(regionCtx, {
                type: 'bar',
                data: {
                    labels: regions,
                    datasets: [{
                        label: 'Number of Students',
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
                        borderWidth: 1,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 5,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(200, 200, 200, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });




            // Admitted students per region
            const admittedRegionCtx = document.getElementById('admitedstudentsPerRegionChart').getContext('2d');
            const admittedRegions = {!! json_encode($admittedstudentsPerRegion->pluck('region')) !!};
            const admittedCounts = {!! json_encode($admittedstudentsPerRegion->pluck('total')) !!};
            const admittedBackgroundColors = generateColors(admittedRegions.length);

            new Chart(admittedRegionCtx, {
                type: 'bar',
                data: {
                    labels: admittedRegions,
                    datasets: [{
                        label: 'Number of Admitted Students',
                        data: admittedCounts,
                        backgroundColor: admittedBackgroundColors,
                        borderColor: admittedBackgroundColors.map(color => color.replace('0.8',
                            '1')),
                        borderWidth: 1,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 5,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(200, 200, 200, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Students per Course Chart
            const courseCtx = document.getElementById('studentsPerCourseChart').getContext('2d');
            new Chart(courseCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($studentsPerCourse->pluck('display_name')) !!},
                    datasets: [{
                        label: 'Number of Students',
                        data: {!! json_encode($studentsPerCourse->pluck('total')) !!},
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 5,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(200, 200, 200, 0.2)'
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Registrations per Day Chart
            const dayCtx = document.getElementById('registrationsPerDayChart').getContext('2d');
            new Chart(dayCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($registrationsPerDay->pluck('date')) !!},
                    datasets: [{
                        label: 'Number of Registrations',
                        data: {!! json_encode($registrationsPerDay->pluck('total')) !!},
                        fill: true,
                        backgroundColor: 'rgba(255, 193, 7, 0.2)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 2,
                        pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 5,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(200, 200, 200, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 0,
                                font: {
                                    size: 9
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Gender Distribution Chart
            const genderCtx = document.getElementById('genderDistributionChart').getContext('2d');
            new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($genderDistribution->pluck('gender')) !!},
                    datasets: [{
                        data: {!! json_encode($genderDistribution->pluck('total')) !!},
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 10,
                                padding: 8,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Age Groups Chart
            const ageCtx = document.getElementById('ageGroupsChart').getContext('2d');
            new Chart(ageCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($ageGroups->pluck('age_group')) !!},
                    datasets: [{
                        label: 'Number of Students',
                        data: {!! json_encode($ageGroups->pluck('total')) !!},
                        backgroundColor: 'rgba(153, 102, 255, 0.8)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(200, 200, 200, 0.2)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
