@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Todos los contactos</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">contactos</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-body">
            <form class="row g-3 align-items-center mb-12 pt-10" method="GET" action="{{ route('contacts.index') }}">
                <div class="col-auto">
                    <select name="country" id="country" class="form-control form-select">
                        <option value="*">-- Seleccionar el país --</option>
                        @foreach($countries as $key => $country)
                            <option value="{{$country}}" {{ request('country') == $country ? 'selected' : '' }}>{{$country}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="date_filter" id="date_filter" class="form-control form-select">
                        <option value="*">-- Selecciona fecha de registro --</option>
                        <option value="{{ now()->startOfWeek()->format('Y-m-d') }}" {{ request('date_filter') == now()->startOfWeek()->format('Y-m-d') ? 'selected' : '' }}>Esta semana</option>
                        <option value="{{ now()->subMonth()->format('Y-m-d') }}" {{ request('date_filter') == now()->subMonth()->format('Y-m-d') ? 'selected' : '' }}>Último mes</option>
                        <option value="{{ now()->subMonths(2)->format('Y-m-d') }}" {{ request('date_filter') == now()->subMonths(2)->format('Y-m-d') ? 'selected' : '' }}>Últimos 2 meses</option>
                        <option value="{{ now()->subMonths(3)->format('Y-m-d') }}" {{ request('date_filter') == now()->subMonths(3)->format('Y-m-d') ? 'selected' : '' }}>Más de 3 meses</option>
                    </select>
                </div>
                <div class="col-auto">
                    <div class="dropdown tag-filter-dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="tagFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="selected-tag-count">Etiquetas</span>
                        </button>
                        <div class="dropdown-menu p-3" aria-labelledby="tagFilterDropdown" style="min-width: 350px;">
                            <div class="tag-checkboxes">
                                <div class="form-check mb-2">
                                    <input class="form-check-input tag-checkbox-all" type="checkbox" name="tag[]" value="*" id="tagAll" {{ in_array('*', (array)request('tag')) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tagAll">Todas las etiquetas</label>
                                </div>
                                <div class="dropdown-divider"></div>
                                @foreach($specialTags as $tag)
                                <div class="form-check mb-2">
                                    <input class="form-check-input tag-checkbox" type="checkbox" name="tag[]" value="{{$tag}}" id="tag_{{$loop->index}}" {{ in_array($tag, (array)request('tag')) ? 'checked' : '' }}>
                                    <label class="form-check-label text-wrap" for="tag_{{$loop->index}}">{{$tag}}</label>
                                </div>
                                @endforeach
                            </div>
                            <div class="dropdown-divider mt-2"></div>
                            <div class="d-flex justify-content-between mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary clear-tags-btn">Limpiar</button>
                                <button type="button" class="btn btn-sm btn-primary apply-tags-btn">Aplicar</button>
                            </div>
                        </div>
                    </div>      
                </div>
                <div class="col-auto ms-auto">
                    <input type="search" name="search" id="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell">#</th>
                            <th>Nombre</th>
                            <th class="d-none d-md-table-cell">País</th>
                            <th>Valor del lead</th>
                            <th>Tiempo de vida</th>
                            <th class="d-none d-md-table-cell">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($noResultsMessage)
                            <tr>
                                <td colspan="7">
                                    <div class="p-4 text-center text-muted">
                                        {{ $noResultsMessage }}
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($contacts as $contact)
                            @php
                                $totalTransactions = $contact->transactions
                                    ->where('status', 'succeeded')
                                    ->sum('amount');
                            @endphp
                            <tr>
                                <td class="d-none d-md-table-cell">{{ $contact->id }}</td>
                                <td>{{ $contact->fullname }}</td>
                                <td class="d-none d-md-table-cell">{{ $contact->country }}</td>
                                <td class="{{ $totalTransactions > 100 ? 'text-success' : 'text-success' }}">{{ $totalTransactions > 0 ? '$'.number_format($totalTransactions, 2).' USD' : '-' }}</td>
                                <td title="{{ \Carbon\Carbon::parse($contact->date_added)->format('d/m/Y H:i:s') }}">{{ \Carbon\Carbon::parse($contact->date_added)->diffForHumans() }}</td>
                                <td class="d-none d-md-table-cell" title="{{ \Carbon\Carbon::parse($contact->date_update)->format('d/m/Y H:i:s') }}">{{ \Carbon\Carbon::parse($contact->date_update)->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total gastado:</td>
                            <td colspan="3" class="{{ $totalAmount > 1000 ? 'text-success fw-bold' : 'text-warning fw-bold' }}">
                                {{ $totalAmount > 0 ? '$'.number_format($totalAmount, 2).' USD' : '-' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-12 d-flex justify-content-between align-items-center">
                <div>
                    <form method="GET" action="{{ route('contacts.index') }}">
                        <input type="hidden" name="country" value="{{ request('country') }}">
                        <input type="hidden" name="date_filter" value="{{ request('date_filter') }}">
                        @if(is_array(request('tag')))
                            @foreach(request('tag') as $tagValue)
                                <input type="hidden" name="tag[]" value="{{ $tagValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="tag" value="{{ request('tag') }}">
                        @endif
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <select name="perPage" class="form-select" onchange="this.form.submit()">
                            <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 por página</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 por página</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 por página</option>
                        </select>
                    </form>
                </div>
                <div>
                    {{ $contacts->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .form-check-label {
        top: -4px;
        position: relative;
        left: 5px;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Contador de etiquetas seleccionadas
        function updateTagCounter() {
            const selectedTags = $('.tag-checkbox:checked').length;
            const allTagsSelected = $('.tag-checkbox-all').is(':checked');

            if (allTagsSelected) {
                $('.selected-tag-count').text('Todas las etiquetas');
            } else if (selectedTags > 0) {
                $('.selected-tag-count').text(`Etiquetas (${selectedTags})`);
            } else {
                $('.selected-tag-count').text('Etiquetas');
            }
        }

        // Inicializar contador
        updateTagCounter();
        
        // Manejar click en "Todas las etiquetas"
        $('.tag-checkbox-all').on('change', function() {
            if ($(this).is(':checked')) {
                $('.tag-checkbox').prop('checked', false).prop('disabled', true);
            } else {
                $('.tag-checkbox').prop('disabled', false);
            }
            updateTagCounter();
        });

        // Deshabilitar "Todas las etiquetas" si se selecciona otra opción
        $('.tag-checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                $('.tag-checkbox-all').prop('checked', false);
                $('.tag-checkbox-all').prop('disabled', true);
            } else if ($('.tag-checkbox:checked').length === 0) {
                $('.tag-checkbox-all').prop('disabled', false);
            }
            updateTagCounter();
        });

        // Limpiar selección de tags
        $('.clear-tags-btn').on('click', function(e) {
            e.stopPropagation();
            $('.tag-checkbox, .tag-checkbox-all').prop('checked', false);
            $('.tag-checkbox, .tag-checkbox-all').prop('disabled', false);
            updateTagCounter();
        });

        // Aplicar filtros
        $('.apply-tags-btn').on('click', function(e) {
            e.stopPropagation();
            // Si "Todas las etiquetas" está seleccionada, asegura que ese valor se envíe correctamente
            if ($('.tag-checkbox-all').is(':checked')) {
                // Elimina todos los otros inputs de tag[] que puedan existir
                $('input[name="tag[]"]:not(.tag-checkbox-all)').prop('disabled', true);
            }
            $(this).closest('form').submit();
        });

        // Configuración inicial basada en el estado actual
        if ($('.tag-checkbox-all').is(':checked')) {
            $('.tag-checkbox').prop('disabled', true);
        } else if ($('.tag-checkbox:checked').length > 0) {
            $('.tag-checkbox-all').prop('disabled', true);
        }

        // Evitar que se cierre el dropdown al hacer clic dentro
        $('.tag-filter-dropdown .dropdown-menu').on('click', function(e) {
            e.stopPropagation();
        });
    });
</script>
@endpush

@push('styles')
<style>
    .tag-checkboxes {
        max-height: 70vh;
        overflow-y: auto;
    }
    .selected-tag-count {
        display: inline-block;
        min-width: 100px;
    }
    /* Estilos para el offcanvas */
    #tagFilterOffcanvas {
        width: 350px;
    }
    #tagFilterOffcanvas .offcanvas-body {
        padding-top: 0;
    }
    .tag-filter-dropdown .dropdown-menu {
        min-width: 250px;
        max-height: 350px;
        overflow-y: auto;
    }
    .tag-checkboxes {
        max-height: 200px;
        overflow-y: auto;
    }
    .selected-tag-count {
        display: inline-block;
        min-width: 100px;
    }
</style>
@endpush