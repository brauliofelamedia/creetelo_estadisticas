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

        <!-- Filtro Global -->
        <div class="card shadow-sm border mb-10">
            <div class="card-body">
                <form action="{{ route('admin.index') }}" method="GET" class="row g-3">
                    <div id="single-month-filter" class="col-md-6 d-flex">
                        <div class="col-md-6 me-2">
                            <label class="form-label">Mes Inicial</label>
                            <input type="month" class="form-control" id="startMonthYear" name="monthYearStart" value="{{ request('monthYearStart', date('Y-m')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mes Final</label>
                            <input type="month" class="form-control" id="endMonthYear" name="monthYearEnd" value="{{ request('monthYearEnd', date('Y-m')) }}">
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="filter_type" id="submit-filter-type" value="range" class="btn btn-success w-100">
                            <i class="fas fa-filter me-1"></i> Aplicar Filtro
                        </button>
                    </div>
                    
                    @if(request('monthYearStart') || request('monthYearEnd'))
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="alert alert-info py-2 mb-0 w-100">
                            <small><i class="fas fa-info-circle me-1"></i> Mostrando datos del período: {{ $filteredPeriod }}</small>
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>
        <!-- Fin Filtro Global -->

        <div class="row row-cols-xxxl-6 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4 mb-10">
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
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Ingresos</p>
                        <h6 class="mb-0">${{number_format(isset($totalCurrentPeriod) ? $totalCurrentPeriod : $totalCurrentYear,0)}} USD</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Ingresos año pasado</p>
                            <h6 class="mb-0">${{number_format($totalLastYear,0)}} USD</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="fluent:people-20-filled" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div>
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
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-5 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Mejor mes - {{ isset($bestMonth['month']) ? $bestMonth['month'] : 'N/A' }} {{ isset($bestMonth['year']) ? $bestMonth['year'] : '' }}</p>
                            <h6 class="mb-0">$ {{number_format(@$bestMonth['amount'],0)}} USD</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-red rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-6 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Tasa de cancelación</p>
                            <h6 class="mb-0">{{number_format($cancellationRate, 1)}}%</h6>
                            <small class="text-muted">{{$cancelledCount}} de {{$totalTransactions}}</small>
                        </div>
                        <div class="w-50-px h-50-px bg-orange rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="mdi:cancel-bold" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    
        <div class="row gy-4">
            <div class="col-xxl-9 col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <h6 class="text-lg mb-0">Ingresos año actual / anterior</h6>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-8">
                            <h6 class="mb-0">${{number_format($totalCurrentYear,0)}} USD (año actual)</h6>
                        </div>
                        <div id="chart" class="pt-28 apexcharts-tooltip-style-1"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                            <h6 class="mb-2 fw-bold text-lg">Medios de pago</h6>
                        </div>
                        <div id="userOverviewDonutChart" class="apexcharts-tooltip-z-none"></div>
                        <ul class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-3">
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-primary-600"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Stripe: <span class="text-primary-light fw-semibold">{{$stripeCount}} - ${{number_format($stripeTotal,2)}} USD</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-yellow"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Paypal: <span class="text-primary-light fw-semibold">{{$paypalCount}} - ${{number_format($paypalTotal,2)}} USD</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-12">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Ingresos diarios de {{ Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY') }}</h6>
                        <div class="d-flex align-items-center gap-2 mb-20">
                            <h6 class="fw-semibold mb-0">${{number_format($totalCurrentMonth,0)}} USD</h6>
                        </div>
                        <div id="dailyChart" class="dailyChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-12">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Ingresos diarios de</h6>
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
    // ================================ Users Overview Donut chart Start ================================ 
    var options = { 
      series: [{{$stripeCount}}, {{$paypalCount}}],
      colors: ['#487FFF', '#FF9F29'],
      labels: ['Stripe', 'PayPal'],
      legend: {
          show: false,
      },
      chart: {
        type: 'donut',    
        height: 270,
        sparkline: {
          enabled: true   
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
        },
      },
      stroke: {
        width: 0,
      },
      dataLabels: {
        enabled: false
      },
      tooltip: {
        enabled: true,
        y: {
          formatter: function(value) {
            return value + " transacciones";
          }
        }
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200,
          },
          legend: {
            position: 'bottom'
          },
        }
      }]
    };
    var chart = new ApexCharts(document.querySelector("#userOverviewDonutChart"), options);
    chart.render();
  // ================================ Users Overview Donut chart End ================================ 
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
            width: 3,
        },
        colors: ['#487FFF', '#FF9F29'],
        markers: {
            size: 0,
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
                formatter: function (value) {
                    return value;
                }
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
                },
            },
        },
        xaxis: {
            categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            labels: {
                style: {
                    fontSize: "14px"
                }
            },
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
    var dailyOptions = {
        series: [
            {
                name: "Ingresos",
                data: {!! json_encode($dailyAmounts) !!}
            }
        ],
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
        }
    };
    var dailyChart = new ApexCharts(document.querySelector("#dailyChart"), dailyOptions);
    dailyChart.render();
</script>
@endpush