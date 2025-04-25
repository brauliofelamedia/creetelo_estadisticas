<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{route('admin.index')}}" class="sidebar-logo">
            <img src="{{asset('storage/'.$config_global->logo_light)}}" alt="site logo" class="light-logo" style="max-width: 180px;" title="{{$config_global->site_name}}">
            <img src="{{asset('storage/'.$config_global->logo_dark)}}" alt="site logo" class="dark-logo" style="max-width: 180px;" title="{{$config_global->site_name}}">
            <img src="{{asset('storage/'.$config_global->favicon)}}" alt="site logo" class="logo-icon" title="{{$config_global->site_name}}">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="sidebar-menu-group-title">Dashboard</li>
                <li>
                    <a href="{{route('admin.index')}}">
                        <iconify-icon icon="ph:chart-line-up" class="menu-icon"></iconify-icon>
                        <span>Estadísticas</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('users.index')}}">
                        <iconify-icon icon="ph:users-three" class="menu-icon"></iconify-icon>
                        <span>Usuarios</span>
                    </a>
                    <a href="{{route('contacts.index')}}">
                        <iconify-icon icon="ph:address-book" class="menu-icon"></iconify-icon>
                        <span>Contactos</span>
                    </a>
                    <a href="{{route('transactions.index')}}">
                        <iconify-icon icon="ph:money" class="menu-icon"></iconify-icon>
                        <span>Transacciones</span>
                    </a>
                    <a href="{{route('subscriptions.index')}}">
                        <iconify-icon icon="ph:bell-ringing" class="menu-icon"></iconify-icon>
                        <span>Subscripciones</span>
                    </a>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="solar:pie-chart-outline" class="menu-icon"></iconify-icon><span>Filtros</span> 
                    </a>
                    <ul class="sidebar-submenu" style="padding-inline-start: 0.5rem;">
                        <li>
                            <a href="{{route('filters')}}"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Filtrar</a>
                        </li>
                        <li>
                            <a href="{{route('filters.actives')}}"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Activos</a>
                        </li>
                        <li>
                            <a href="{{route('filters.subscriptions')}}"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Subscripciones</a>
                        </li>
                    </ul>
                </li>
            </li>
            @role('super_admin')
                <li class="sidebar-menu-group-title">Sistema</li>
                    <li style="display: none;">
                        <a href="#">
                            <iconify-icon icon="carbon:user-role" class="menu-icon"></iconify-icon>
                            <span>Roles & permisos</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('config.edit','0195aa88-19d7-7045-9116-fb9f61b75e4c')}}">
                            <iconify-icon icon="mage:email" class="menu-icon"></iconify-icon>
                            <span>Configuración</span>
                        </a>
                    </li>
                </li>
            @endrole
        </ul>
    </div>
</aside>