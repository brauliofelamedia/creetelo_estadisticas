@extends('layouts.simple')

@section('content')
    <section class="auth bg-base d-flex flex-wrap">  
        <div class="auth-left d-lg-block d-none" style="background-image:url('{{asset('assets/images/banner.png')}}');background-size: cover;background-position: right;background-repeat: no-repeat;">
            <div class="d-flex align-items-center flex-column h-100 justify-content-center" style="display: none;"></div>
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100">
                <div>
                    <a href="{{route('login')}}" class="mb-40 max-w-290-px text-center mx-auto d-block">
                        <img src="{{asset('assets/images/logo.webp')}}" alt="{{$config_global->site_name}}" style="max-width:80%;">
                    </a>
                    <h5 class="mb-12">Iniciar sesión en tu cuenta</h5>
                    <p class="mb-32 text-secondary-light text-lg">¡Bienvenido de vuelta! por favor ingresa tus datos</p>
                </div>
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="icon-field mb-16">
                        <span class="icon top-50 translate-middle-y">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" name="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" 
                            placeholder="Correo electrónico" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    </div>
                    <div class="position-relative mb-20">
                        <div class="icon-field">
                            <span class="icon top-50 translate-middle-y">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span> 
                            <input type="password" name="password" class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror" 
                                id="your-password" placeholder="Contraseña" required autocomplete="current-password">
                        </div>
                        <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" 
                            data-toggle="#your-password"></span>
                    </div>
                    <div class="">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="form-check style-check d-flex align-items-center">
                                <input class="form-check-input border border-neutral-300" type="checkbox" name="remember" 
                                    id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                            <a href="{{ route('forgot.password') }}" class="text-primary-600 fw-medium">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary fw-semibold text-md px-24 py-16 w-100 radius-12 mt-32 d-flex align-items-center justify-content-center gap-12">
                        <iconify-icon icon="solar:login-2-outline" class="text-xl line-height-1"></iconify-icon>
                        Iniciar Sesión
                    </button>
                    <div class="mt-32 center-border-horizontal text-center">
                        <span class="bg-base z-1 px-4">O inicia sesión con</span>
                    </div>
                    <div class="mt-32 d-flex align-items-center">
                        <a href="{{ route('magic.login') }}" class="fw-semibold text-primary-light py-16 px-24 w-100 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50"> 
                            <iconify-icon icon="mdi:magic" class="text-primary-600 text-xl line-height-1"></iconify-icon>
                            Iniciar sesión con enlace mágico
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection