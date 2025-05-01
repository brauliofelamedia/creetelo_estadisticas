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
                    <h5 class="mb-12">Recuperar contraseña</h5>
                    <p class="mb-32 text-secondary-light text-lg">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña</p>
                </div>
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('forgot.password') }}">
                    @csrf
                    <div class="icon-field mb-16">
                        <span class="icon top-50 translate-middle-y">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" name="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" placeholder="Correo electrónico" value="{{ old('email') }}" required autocomplete="email" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary fw-semibold text-md px-24 py-16 w-100 radius-12 mt-32 d-flex align-items-center justify-content-center gap-12">
                        <iconify-icon icon="mdi:key-chain-variant" class="text-xl line-height-1"></iconify-icon>
                        Recuperar contraseña
                    </button>
                    <a href="{{ route('login') }}" class="d-block mt-16 text-center text-primary">Volver al inicio de sesión</a>
                </form>
            </div>
        </div>
    </section>
@endsection