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

        <div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Contactos activos</p>
                        <h6 class="mb-0">{{$currentUsers}}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
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
                <div class="card shadow-none border bg-gradient-start-3 h-100">
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
                            <p class="fw-medium text-primary-light mb-1">Mejor mes - {{$bestMonth['month']}}</p>
                            <h6 class="mb-0">$ {{number_format($bestMonth['amount'],0)}} USD</h6>
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
                    <h6 class="text-lg mb-0">Ingresos año actual</h6>
                    <select class="form-select bg-base form-select-sm w-auto" style="display: none;">
                        <option>Yearly</option>
                        <option>Monthly</option>
                        <option>Weekly</option>
                        <option>Today</option>
                    </select>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-8">
                    <h6 class="mb-0">${{number_format($totalCurrentYear,0)}} USD</h6>
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
                            <h6 class="fw-semibold mb-0">${{$currentWeekAmount}} USD</h6>
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
            <div class="col-xxl-9 col-xl-12">
                <div class="card h-100">
                    <div class="card-body p-24">

                    <div class="d-flex flex-wrap align-items-center gap-1 justify-content-between mb-16">
                        <ul class="nav border-gradient-tab nav-pills mb-0" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-flex align-items-center active" id="pills-to-do-list-tab" data-bs-toggle="pill" data-bs-target="#pills-to-do-list" type="button" role="tab" aria-controls="pills-to-do-list" aria-selected="true">
                                    Último registrados
                                <span class="text-sm fw-semibold py-6 px-12 bg-neutral-500 rounded-pill text-white line-height-1 ms-12 notification-alert">{{$currentUsers}}</span>
                                </button>
                            </li>
                        </ul>
                        <a href="{{route('users.index')}}" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1"> Ver todos
                        <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                        </a>
                    </div>
                    <div class="tab-content" id="pills-tabContent">   
                        <div class="tab-pane fade show active" id="pills-to-do-list" role="tabpanel" aria-labelledby="pills-to-do-list-tab" tabindex="0">
                            <div class="table-responsive scroll-sm">
                                <table class="table bordered-table sm-table mb-0">
                                <thead>
                                    <tr>
                                    <th scope="col">Usuario </th>
                                    <th scope="col">Registrado</th>
                                    <th scope="col">Rol</th>
                                    <th scope="col" class="text-center">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($latestUsers as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                <img src="{{asset('storage/'.$user->avatar)}}" alt="" class="w-40-px h-40-px rounded-circle flex-shrink-0 me-12 overflow-hidden">
                                                <div class="flex-grow-1">
                                                    <h6 class="text-md mb-0 fw-medium">{{$user->fullname}}</h6>
                                                    <span class="text-sm text-secondary-light fw-medium">{{$user->email}}</span>
                                                </div>
                                                </div>
                                            </td>
                                            <td>{{$user->created_at->format('d-m-Y')}}</td>
                                            <td>{{$user->role}}</td>
                                            <td class="text-center"> 
                                                @if($user->status == true)
                                                    <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Activo</span>
                                                @else
                                                    <span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill fw-medium text-sm">Inactivo</span>
                                                @endif
                                            </td>
                                        </tr> 
                                    @endforeach
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var options = {
        series: [{
        name: "Este mes",
        data: {!! json_encode($monthlyAmounts) !!}
        }],
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
        colors: ['#487FFF'],
        width: 3
        },
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
            // gradient: {
            //   colorFrom: '#D8E3F0',
            //   // colorTo: '#BED1E6',
            //   stops: [0, 100],
            //   opacityFrom: 0.4,
            //   opacityTo: 0.5,
            // },
            }
        }
        }
    };
    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
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
@endpush