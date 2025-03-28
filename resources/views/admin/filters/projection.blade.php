@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Proyección de Ventas</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Proyección</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Seleccionar período de proyección</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('filters.projection') }}" method="GET">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-10">
                                    <label class="label-bold">Período de proyección</label>
                                    <select name="period" class="form-control" id="projectionPeriod">
                                        <option value="" selected>-- Selecciona el periodo a calcular --</option>
                                        <option value="3" {{ request('period', $projectionPeriod) == 3 ? 'selected' : '' }}>3 meses</option>
                                        <option value="6" {{ request('period', $projectionPeriod) == 6 ? 'selected' : '' }}>6 meses</option>
                                        <option value="12" {{ request('period', $projectionPeriod) == 12 ? 'selected' : '' }}>12 meses</option>
                                    </select>
                                </div>
                                <label class="label-bold">Membresías</label>
                                @foreach($sources as $key => $source)
                                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                                        <input class="form-check-input form-check-input-sm" 
                                            name="sources[]" 
                                            type="checkbox" 
                                            id="source-{{ $key }}" 
                                            value="{{ urlencode($source) }}"
                                            {{ in_array($source, $selectedSources) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="source-{{ $key }}">
                                                {{ str_replace('- Payment', '', $source) }}
                                            </label>
                                    </div>
                                @endforeach
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="width:100%;" id="selectAll">Seleccionar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="width:100%;" id="deselectAll">Deseleccionar</button>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Calcular proyección</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(isset($historicalData))
            <div class="col-lg-9">
                @foreach($historicalData as $sourceName => $sourceData)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">{{ str_replace('- Payment', '', $sourceName) }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mes</th>
                                        <th>Total</th>
                                        <th>Exitosas</th>
                                        <th>Fallidas</th>
                                        <th>Reembolsadas</th>
                                        <th>Monto Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sourceData as $data)
                                    <tr>
                                        <td>{{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $data['month'])->translatedFormat('M Y')) }}</td>
                                        <td>{{ $data['total'] }}</td>
                                        <td>
                                            <span>{{ $data['succeeded'] }}</span>
                                            @if($data['succeeded_growth'] != 0)
                                            <small class="ms-1 {{ $data['succeeded_growth'] > 0 ? 'text-success' : 'text-danger' }}">
                                                ({{ $data['succeeded_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($data['succeeded_growth']), 1) }}%)
                                            </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ $data['failed'] }}</span>
                                            @if($data['failed_growth'] != 0)
                                            <small class="ms-1 {{ $data['failed_growth'] > 0 ? 'text-danger' : 'text-danger' }}">
                                                ({{ $data['failed_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($data['failed_growth']), 1) }}%)
                                            </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ $data['refunded'] }}</span>
                                            @if($data['refunded_growth'] != 0)
                                            <small class="ms-1 {{ $data['refunded_growth'] > 0 ? 'text-danger' : 'text-danger' }}">
                                                ({{ $data['refunded_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($data['refunded_growth']), 1) }}%)
                                            </small>
                                            @endif
                                        </td>
                                        <td>USD ${{ number_format($data['total_amount'], 2) }}</td>
                                    </tr>
                                    @endforeach

                                    @foreach($projectedData[$sourceName] ?? [] as $projection)
                                        <tr class="table-info">
                                            <td>
                                                {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $projection['month'])->translatedFormat('M Y')) }}
                                                <span class="badge bg-info">Proyección</span>
                                            </td>
                                            <td>{{ $projection['total'] }}</td>
                                            <td>
                                                <span>{{ $projection['succeeded'] }}</span>
                                                @if($projection['succeeded_growth'] != 0)
                                                <small class="ms-1 {{ $projection['succeeded_growth'] > 0 ? 'text-success' : 'text-danger' }}">
                                                    ({{ $projection['succeeded_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($projection['succeeded_growth']), 1) }}%)
                                                </small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-danger">{{ $projection['failed'] }}</span>
                                                @if($projection['failed_growth'] != 0)
                                                <small class="ms-1 {{ $projection['failed_growth'] > 0 ? 'text-danger' : 'text-danger' }}">
                                                    ({{ $projection['failed_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($projection['failed_growth']), 1) }}%)
                                                </small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-danger">{{ $projection['refunded'] }}</span>
                                                @if($projection['refunded_growth'] != 0)
                                                <small class="ms-1 {{ $projection['refunded_growth'] > 0 ? 'text-danger' : 'text-danger' }}">
                                                    ({{ $projection['refunded_growth'] > 0 ? '↑' : '↓' }} {{ number_format(abs($projection['refunded_growth']), 1) }}%)
                                                </small>
                                                @endif
                                            </td>
                                            <td>USD ${{ number_format($projection['total_amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>Totales Históricos</td>
                                        <td>{{ collect($sourceData)->sum('total') }}</td>
                                        <td>{{ collect($sourceData)->sum('succeeded') }}</td>
                                        <td class="text-danger">{{ collect($sourceData)->sum('failed') }}</td>
                                        <td class="text-danger">{{ collect($sourceData)->sum('refunded') }}</td>
                                        <td>USD ${{ number_format(collect($sourceData)->sum('total_amount'), 2) }}</td>
                                    </tr>
                                    @if(isset($projectedData[$sourceName]) && count($projectedData[$sourceName]) > 0)
                                    <tr>
                                        <td>Totales Proyectados</td>
                                        <td>{{ collect($projectedData[$sourceName])->sum('total') }}</td>
                                        <td>{{ collect($projectedData[$sourceName])->sum('succeeded') }}</td>
                                        <td class="text-danger">{{ collect($projectedData[$sourceName])->sum('failed') }}</td>
                                        <td class="text-danger">{{ collect($projectedData[$sourceName])->sum('refunded') }}</td>
                                        <td>USD ${{ number_format(collect($projectedData[$sourceName])->sum('total_amount'), 2) }}</td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#selectAll').click(function() {
            $('input[name="sources[]"]').prop('checked', true);
        });
        
        $('#deselectAll').click(function() {
            $('input[name="sources[]"]').prop('checked', false);
        });
    });
    </script>
@endpush

@push('css')
<style>
    .form-check-label {
        position: relative;
        top: -2px;
        left: 5px;
    }
    .label-bold {
        font-weight: bold;
        margin-bottom: 5px;
    }
</style>
@endpush