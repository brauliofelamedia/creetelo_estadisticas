@extends('layouts.app')

@section('content')

    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Dashboard</h6>
            <ul class="d-flex align-items-center gap-2">
                <li class="fw-medium">
                <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
                </li>
                <li>-</li>
                <li class="fw-medium">Estadísticas</li>
            </ul>
        </div>

        @if(session()->has('message'))
            <div class="col-12">
                <div class="col-12">
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                </div>
            </div>
       @endif

        <div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Contactos</p>
                        <h6 class="mb-0">{{number_format($currentContacts,0)}}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                    </div>
                </div>
                </div><!-- card end -->
            </div>

            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Año actual</p>
                        <h6 class="mb-0">${{number_format($totalCurrentYear,0)}} USD</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                    </div>
                </div>
                </div><!-- card end -->
            </div>

            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Año pasado</p>
                            <h6 class="mb-0">${{number_format($totalLastYear,0)}} USD</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="fluent:people-20-filled" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div><!-- card end -->
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-4 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Mes actual</p>
                            <h6 class="mb-0">${{number_format($totalCurrentMonth,0)}} USD</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:wallet-bold" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div><!-- card end -->
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-5 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Mejor mes - {{@$bestMonth['month']}}</p>
                            <h6 class="mb-0">$ {{number_format(@$bestMonth['amount'],0)}} USD</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-red rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div><!-- card end -->
            </div>
        </div>

        <div class="row gy-4 mt-1">
            
            <div class="col-xxl-6 col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <h6 class="text-lg mb-0">Ingresos año actual vs anterior</h6>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-8">
                            <h6 class="mb-0">${{number_format($totalCurrentYear,0)}} USD (año actual)</h6>
                        </div>
                        <div id="chart" class="pt-28 apexcharts-tooltip-style-1"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card h-100 radius-8 border">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Ingresos semanales</h6>
                        <div class="d-flex align-items-center gap-2 mb-20">
                            <h6 class="fw-semibold mb-0">${{number_format($currentWeekAmount,0)}} USD</h6>
                        </div>
                        <div id="barChart" class="barChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                        <h6 class="mb-2 fw-bold text-lg">Medios de pago</h6>
                        <div style="display: none;">
                            <select class="form-select form-select-sm w-auto bg-base border text-secondary-light">
                                <option>Today</option>
                                <option>Weekly</option>
                                <option>Monthly</option>
                                <option>Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div id="userOverviewDonutChart" class="apexcharts-tooltip-z-none"></div>

                    <ul class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-3">
                        <li class="d-flex align-items-center gap-2">
                            <span class="w-12-px h-12-px radius-2 bg-primary-600"></span>
                            <span class="text-secondary-light text-sm fw-normal">Stripe: 
                                <span class="text-primary-light fw-semibold">{{$stripe}}</span>
                            </span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="w-12-px h-12-px radius-2 bg-yellow"></span>
                            <span class="text-secondary-light text-sm fw-normal">Paypal:  
                                <span class="text-primary-light fw-semibold">{{$paypal}}</span>
                            </span>
                        </li>
                    </ul>
                    
                </div>
                </div>
            </div>
            <!-- Add the new chart here -->
            <div class="col-xxl-6 col-xl-12">
                <div class="card h-100 radius-8 border">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Ingresos diarios del mes</h6>
                        <div class="d-flex align-items-center gap-2 mb-20">
                            <h6 class="fw-semibold mb-0">${{number_format($totalCurrentMonth,0)}} USD</h6>
                        </div>
                        <div id="dailyChart" class="dailyChart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var options = {
        series: [
            {
                name: "Este año",
                data: {!! json_encode($monthlyAmounts) !!}
            },
            {
                name: "Año anterior",
                data: {!! json_encode($lastYearMonthlyAmounts) !!}
            }
        ],
        chart: {
            height: 264,
            type: 'line',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            dropShadow: {
                enabled: true,
                top: 6,
                left: 0,
                blur: 4,
                color: "#000",
                opacity: 0.1,
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#487FFF', '#FF9F29'],
        markers: {
            size: 0,
            strokeWidth: 3,
            hover: {
                size: 8
            }
        },
        tooltip: {
            enabled: true,
            x: {
                show: true,
            },
            y: {
                show: false,
            },
            z: {
                show: false,
            }
        },
        grid: {
            row: {
                colors: ['transparent', 'transparent'],
                opacity: 0.5
            },
            borderColor: '#D1D5DB',
            strokeDashArray: 3,
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return "$" + value + " USD";
                },
                style: {
                    fontSize: "14px"
                }
            },
        },
        xaxis: {
            categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            tooltip: {
                enabled: false
            },
            labels: {
                formatter: function (value) {
                    return value;
                },
                style: {
                    fontSize: "14px"
                }
            },
            axisBorder: {
                show: false
            },
            crosshairs: {
                show: true,
                width: 20,
                stroke: {
                    width: 0
                },
                fill: {
                    type: 'solid',
                    color: '#487FFF40',
                }
            }
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            offsetY: -30,
            markers: {
                width: 10,
                height: 10,
                radius: 12,
            },
            itemMargin: {
                horizontal: 10,
                vertical: 0
            },
        }
    };
    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
</script>
<script>
    var optionsLastYear = {
        series: [
            {
                name: "Año anterior",
                data: {!! json_encode($lastYearMonthlyAmounts) !!}
            },
            {
                name: "Dos años atrás",
                data: {!! json_encode($lastYearMonthlyAmounts) ?? json_encode(array_fill(0, 12, 0)) !!} // Reemplazar con datos reales de 2 años atrás si están disponibles
            }
        ],
        chart: {
            height: 264,
            type: 'line',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            dropShadow: {
                enabled: true,
                top: 6,
                left: 0,
                blur: 4,
                color: "#000",
                opacity: 0.1,
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#FF9F29', '#34D399'],
        markers: {
            size: 0,
            strokeWidth: 3,
            hover: {
                size: 8
            }
        },
        tooltip: {
            enabled: true,
            x: {
                show: true,
            },
            y: {
                show: false,
            },
            z: {
                show: false,
            }
        },
        grid: {
            row: {
                colors: ['transparent', 'transparent'],
                opacity: 0.5
            },
            borderColor: '#D1D5DB',
            strokeDashArray: 3,
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return "$" + value + " USD";
                },
                style: {
                    fontSize: "14px"
                }
            },
        },
        xaxis: {
            categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            tooltip: {
                enabled: false
            },
            labels: {
                formatter: function (value) {
                    return value;
                },
                style: {
                    fontSize: "14px"
                }
            },
            axisBorder: {
                show: false
            },
            crosshairs: {
                show: true,
                width: 20,
                stroke: {
                    width: 0
                },
                fill: {
                    type: 'solid',
                    color: '#FF9F2940',
                }
            }
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            offsetY: -30,
            markers: {
                width: 10,
                height: 10,
                radius: 12,
            },
            itemMargin: {
                horizontal: 10,
                vertical: 0
            },
        }
    };
    var chartLastYear = new ApexCharts(document.querySelector("#chartLastYear"), optionsLastYear);
    chartLastYear.render();
</script>
<script>
    var options = { 
      series: [{!! json_encode($paypal) !!}, {!! json_encode($stripe) !!}],
      colors: ['#FF9F29', '#487FFF'],
      labels: ['PayPal', 'Stripe'] ,
      legend: {
          show: false 
      },
      chart: {
        type: 'donut',    
        height: 270,
        sparkline: {
          enabled: true // Remove whitespace
        },
        margin: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        },
        padding: {
          top: 0,
          right: 0,
          bottom: 0,
          left: 0
        }
      },
      stroke: {
        width: 0,
      },
      dataLabels: {
        enabled: false
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }],
    };

    var chart = new ApexCharts(document.querySelector("#userOverviewDonutChart"), options);
    chart.render();
</script>
<script>
    var options = {
    series: [{
        name: "Ingresos",
        data: {!! json_encode($weeklyAmounts) !!}
      }],
      chart: {
          type: 'bar',
          height: 235,
          toolbar: {
              show: false
          },
      },
      plotOptions: {
          bar: {
            borderRadius: 6,
            horizontal: false,
            columnWidth: 24,
            columnWidth: '52%',
            endingShape: 'rounded',
          }
      },
      dataLabels: {
          enabled: false
      },
      fill: {
          type: 'gradient',
          colors: ['#dae5ff'], // Set the starting color (top color) here
          gradient: {
              shade: 'light', // Gradient shading type
              type: 'vertical',  // Gradient direction (vertical)
              shadeIntensity: 0.5, // Intensity of the gradient shading
              gradientToColors: ['#dae5ff'], // Bottom gradient color (with transparency)
              inverseColors: false, // Do not invert colors
              opacityFrom: 1, // Starting opacity
              opacityTo: 1,  // Ending opacity
              stops: [0, 100],
          },
      },
      grid: {
          show: false,
          borderColor: '#D1D5DB',
          strokeDashArray: 4,
          position: 'back',
          padding: {
            top: -10,
            right: -10,
            bottom: -10,
            left: -10
          }
      },
      xaxis: {
          type: 'category',
          categories: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom']
      },
      yaxis: {
        show: false,
      },
  };

  var chart = new ApexCharts(document.querySelector("#barChart"), options);
  chart.render();
</script>
<script>
    var dailyOptions = {
        series: [{
            name: "Ingresos",
            data: {!! json_encode($dailyAmounts) !!}
        }],
        chart: {
            type: 'bar',
            height: 235,
            toolbar: {
                show: false
            },
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: false,
                columnWidth: '52%',
                endingShape: 'rounded',
            }
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
            colors: ['#487FFF'],
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#487FFF'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 100],
            },
        },
        grid: {
            show: true,
            borderColor: '#D1D5DB',
            strokeDashArray: 4,
            position: 'back',
        },
        xaxis: {
            categories: Array.from({length: {!! Carbon\Carbon::now()->daysInMonth !!}}, (_, i) => i + 1),
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return "$" + value + " USD";
                }
            },
        },
    };

    var dailyChart = new ApexCharts(document.querySelector("#dailyChart"), dailyOptions);
    dailyChart.render();
</script>
@endpush