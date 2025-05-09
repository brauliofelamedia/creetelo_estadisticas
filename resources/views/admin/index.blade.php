@extends('layouts.app')

@section('title', 'Analíticas')

@section('content')

    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Analíticas - Créetelo Club</h6>
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
                            <span class="normal-state">
                                <i class="fas fa-filter me-1"></i> Aplicar filtro
                            </span>
                            <span class="loading-state d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span class="ms-1">Procesando...</span>
                            </span>
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

        <!-- Debug Info - Solo visible para administradores -->
        @if(isset($debugInfo) && auth()->user() && auth()->user()->is_admin)
        <div class="card shadow-sm border mb-10">
            <div class="card-header bg-light">
                <h6 class="mb-0">Información de depuración</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Fecha inicio:</strong> {{ $debugInfo['startDate'] }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Fecha fin:</strong> {{ $debugInfo['endDate'] }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Filtro activo:</strong> {{ $debugInfo['hasFilter'] ? 'Sí' : 'No' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Transacciones filtradas:</strong> {{ $debugInfo['totalFilteredTransactions'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!-- Fin Debug Info -->

        <div class="row row-cols-xxxl-6 row-cols-lg-4 row-cols-sm-2 row-cols-1 gy-4 mb-10">
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Contactos nuevos</p>
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
                <div class="card shadow-none border bg-gradient-start-7 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Membresías activas</p>
                        <h6 class="mb-0">{{$activeMemberships->count()}}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="mdi:account-check" class="text-white text-2xl mb-0"></iconify-icon>
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
                        <p class="fw-medium text-primary-light mb-1">Membresías canceladas</p>
                        <h6 class="mb-0">{{$canceledSubscriptions->count()}}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-danger rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="mdi:account-cancel" class="text-white text-2xl mb-0"></iconify-icon>
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
                        <h6 class="mb-0">${{number_format($totalAmount,0)}} USD</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <small class="text-muted">{{$canceledSubscriptions->count()}} de {{$totalTransactions}}</small>
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
            <div class="col-xxl-6 col-xl-6">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Membresías por tipo y fuente</h6>
                        <div id="membershipsBySourceAndStatusChart" class="membershipsBySourceAndStatusChart"></div>
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
                                <span class="w-12-px h-12-px radius-2 bg-orange"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Paypal: <span class="text-primary-light fw-semibold">{{$paypalCount}} - ${{number_format($paypalTotal,2)}} USD</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                            <h6 class="mb-2 fw-bold text-lg">Estados de membresías</h6>
                        </div>
                        <div id="contactStatusDonutChart" class="apexcharts-tooltip-z-none"></div>
                        <ul class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-3">
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-success-main"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Activos: <span class="text-success fw-semibold">{{ $activeSubscriptions->count()}}</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-danger"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Cancelados: <span class="text-danger fw-semibold">{{$canceledSubscriptions->count()}}</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-info"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Prueba: <span class="text-info fw-semibold">{{$trialingSubscriptions->count()}}</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-warning"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Pausados: <span class="text-warning fw-semibold">{{$pausedSubscriptions->count()}}</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-secondary"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Vencidos: <span class="text-secondary fw-semibold">{{$pastDueSubscriptions->count()}}</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="w-12-px h-12-px radius-2 bg-muted"></span>
                                <span class="text-secondary-light text-sm fw-normal">
                                    Expirados: <span class="text-muted fw-semibold">{{$incompleteExpiredSubscriptions->count()}}</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Nueva gráfica para el ticket promedio por mes -->
            <div class="col-xxl-12 col-xl-12">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Evolución del ticket promedio</h6>
                        <div class="d-flex align-items-center gap-2 mb-20">
                            <h6 class="fw-semibold mb-0">Periodo: {{$filteredPeriod}}</h6>
                        </div>
                        <div id="averageTicketChart" class="averageTicketChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5>Otros datos de interés</h5><hr>
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
            <!-- Nueva tarjeta para el ticket promedio -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Ticket Promedio</p>
                            <h6 class="mb-0">${{number_format($averageTicket,2)}} USD</h6>
                            <small class="text-muted">Basado en {{$totalTransactionsInRange}} transacciones</small>
                        </div>
                        <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="mdi:ticket-percent" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="col-xxl-12 col-xl-12">
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
            <div class="col-xxl-12 col-xl-12">
                <div class="card h-100 radius-8 border-0 overflow-hidden">
                    <div class="card-body p-24">
                        <h6 class="mb-12 fw-semibold text-lg mb-16">Ingresos diarios x día {{ Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY') }} <br><small class="text-muted">Se muestra los ingresos diarios por tipo de pago</small></h6>
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
            position: 'bottom',
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
            parentHeightOffset: 0,
            offsetX: 0,
            offsetY: 0,
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
                    return "$" + value.toLocaleString('en-US') + " USD";
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
                    return "$" + value.toLocaleString('en-US');
                },
                style: {
                    fontSize: "13px",
                    fontWeight: "500"
                },
                offsetX: -15,
                show: false, // Hide the y-axis labels
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
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
                name: "Stripe",
                data: {!! json_encode($dailyStripeAmounts) !!}
            },
            {
                name: "PayPal",
                data: {!! json_encode($dailyPaypalAmounts) !!}
            }
        ],
        chart: {
            type: 'bar',
            height: 235,
            toolbar: {
                show: false
            },
            stacked: true,
            parentHeightOffset: 0,
            offsetX: 0,
            offsetY: 0,
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
            colors: ['#487FFF', '#FF9F29'],
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#487FFF', '#FF9F29'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
        },
        colors: ['#487FFF', '#FF9F29'], // Explicitly set colors to ensure Stripe is blue and PayPal is orange
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
                    return "$" + value;
                },
                style: {
                    fontSize: "12px"
                },
                offsetX: -10,
                show: false,
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            },
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
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
    var dailyChart = new ApexCharts(document.querySelector("#dailyChart"), dailyOptions);
    dailyChart.render();

    // ================================ Contact Status Donut chart Start ================================
    var contactStatusOptions = {
      series: [
        {{$activeSubscriptions->count()}}, 
        {{$canceledSubscriptions->count()}}, 
        {{$trialingSubscriptions->count()}}, 
        {{$pausedSubscriptions->count()}}, 
        {{$pastDueSubscriptions->count()}}, 
        {{$incompleteExpiredSubscriptions->count()}}
      ],
      colors: ['#28a745', '#dc3545', '#17a2b8', '#ffc107', '#6c757d', '#ced4da'],
      labels: ['Activos', 'Cancelados', 'Prueba', 'Pausados', 'Vencidos', 'Expirados'],
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
            return value + " contactos";
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
    var contactStatusChart = new ApexCharts(document.querySelector("#contactStatusDonutChart"), contactStatusOptions);
    contactStatusChart.render();
    // ================================ Contact Status Donut chart End ================================
    
    // ================================ Memberships by Source and Status Chart Start ================================
    var membershipsBySourceOptions = {
        series: {!! json_encode($membershipBarSeries) !!},
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false,
                }
            },
            parentHeightOffset: 0,
            offsetX: 0,
            offsetY: 0,
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 4,
                dataLabels: {
                    total: {
                        enabled: true,
                        style: {
                            fontSize: '13px',
                            fontWeight: 900
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: {!! json_encode(array_map(function($status) use ($statusesTranslated) {
                return $statusesTranslated[$status] ?? ucfirst($status);
            }, $statuses)) !!},
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Cantidad de membresías',
                offsetX: -10,
            },
            labels: {
                style: {
                    fontSize: '12px'
                },
                offsetX: -10,
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            },
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " membresías";
                }
            }
        },
        fill: {
            opacity: 1
        },
        colors: ['#487FFF', '#28a745', '#17a2b8', '#6c757d'],
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '13px',
            markers: {
                width: 10,
                height: 10,
                radius: 4
            }
        }
    };
    var membershipsBySourceChart = new ApexCharts(document.querySelector("#membershipsBySourceAndStatusChart"), membershipsBySourceOptions);
    membershipsBySourceChart.render();
    
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('form[action="{{ route("admin.index") }}"]');
        const submitButton = document.getElementById('submit-filter-type');
        const normalState = submitButton.querySelector('.normal-state');
        const loadingState = submitButton.querySelector('.loading-state');
        
        filterForm.addEventListener('submit', function() {
            // Show loading state
            normalState.classList.add('d-none');
            loadingState.classList.remove('d-none');
            
            // Disable the button to prevent multiple submissions
            submitButton.disabled = true;
        });
    });

    // Nueva gráfica de ticket promedio
    var averageTicketOptions = {
        series: [{
            name: "Ticket Promedio",
            data: {!! json_encode(array_map(function($item) { return round($item['average'], 2); }, array_reverse($averageTicketByMonth))) !!}
        }, {
            name: "Número de Transacciones",
            data: {!! json_encode(array_map(function($item) { return $item['count']; }, array_reverse($averageTicketByMonth))) !!}
        }],
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: false,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false,
                }
            },
            parentHeightOffset: 0,
            offsetX: 0,
            offsetY: 0,
        },
        stroke: {
            width: [3, 1],
            curve: 'smooth',
            dashArray: [0, 5]
        },
        colors: ['#487FFF', '#28a745'],
        markers: {
            size: 5,
            hover: {
                size: 8
            }
        },
        xaxis: {
            categories: {!! json_encode(array_map(function($item) { return $item['month']; }, array_reverse($averageTicketByMonth))) !!}
        },
        yaxis: [
            {
                labels: {
                    show: false,
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false,
                },
                seriesName: "Ticket promedio"
            },
            {
                opposite: true,
                title: {
                    text: 'Número de transacciones',
                    offsetX: 10,
                },
                min: 0,
                labels: {
                    show: false,
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false,
                },
                seriesName: "Número de Transacciones"
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: [{
                formatter: function(y) {
                    if(typeof y !== "undefined") {
                        return "$" + y.toFixed(2) + " USD";
                    }
                    return y;
                }
            }, {
                formatter: function(y) {
                    if(typeof y !== "undefined") {
                        return y.toFixed(0) + " transacciones";
                    }
                    return y;
                }
            }]
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };
    
    var averageTicketChart = new ApexCharts(document.querySelector("#averageTicketChart"), averageTicketOptions);
    averageTicketChart.render();
</script>
@endpush