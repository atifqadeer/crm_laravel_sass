@extends('layouts.auth', ['title' => 'Login'])

@section('content')
    <div class="col-xl-5 col-lg-5 col-md-8">
        <div class="card auth-card">
            <div class="card-body px-3 py-5">
                <div class="mx-auto mb-4 text-center auth-logo">
                    <a href="{{ route('dashboard.index')}}" class="logo-dark">
                        <img src="{{ asset('images/logo-dark.png') }}" height="60" alt="crm">
                    </a>

                    <a href="{{ route('dashboard.index')}}" class="logo-light">
                        <img src="{{ asset('images/logo-light.png') }}" height="60" alt="crm">
                    </a>
                </div>

                <h2 class="fw-bold text-uppercase text-center fs-18">Sign In</h2>
                <p class="text-muted text-center mt-1 mb-4">Enter your email address and password to access admin
                    panel.</p>

                <div class="px-4">
                    <form method="POST" action="{{ route('login') }}" class="authentication-form">
                        @csrf
                        @if (sizeof($errors) > 0)
                            @foreach ($errors->all() as $error)
                                <p class="text-danger mb-3">{{ $error }}</p>
                            @endforeach
                        @endif

                        <div class="mb-3">
                            <label class="form-label" for="example-email">Email</label>
                            <input type="email" id="example-email" name="email"
                                   class="form-control bg-light bg-opacity-50 border-light py-2"
                                   placeholder="Enter your email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="example-password">Password</label>
                            <div class="input-group">
                                <input type="password" id="example-password"
                                       class="form-control bg-light bg-opacity-50 border-light py-2"
                                       placeholder="Enter your password" name="password">
                                <button class="btn btn-light bg-opacity-50 border-light" type="button" id="togglePassword">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>
                        </div>
                        {{-- <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="remember" id="checkbox-signin">
                                <label class="form-check-label" for="checkbox-signin">Remember me</label>
                            </div>
                        </div> --}}

                        <div class="mb-1 text-center d-grid">
                            <button class="btn btn-danger py-2 fw-medium" type="submit">Sign In</button>
                        </div>
                    </form>
                </div> <!-- end col -->
            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </p>
</div>
@endsection
@section('script-bottom')
<script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            var toggleBtn = document.getElementById('togglePassword');
            var passwordInput = document.getElementById('example-password');
            
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    var icon = this.querySelector('i');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('ri-eye-line');
                        icon.classList.add('ri-eye-off-line');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('ri-eye-off-line');
                        icon.classList.add('ri-eye-line');
                    }
                });
            }
        });
    })();
</script>
@endsection
