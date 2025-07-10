@extends('layouts.auth', ['title' => 'Login'])

@section('content')
    <div class="col-xl-5">
        <div class="card auth-card">
            <div class="card-body px-3 py-5">
                <div class="mx-auto mb-4 text-center auth-logo">
                    <a href="{{ route('second', ['dashboards', 'analytics'])}}" class="logo-dark">
                        <img src="/images/logo-dark.png" height="60" alt="logo dark">
                    </a>

                    <a href="{{ route('second', ['dashboards', 'analytics'])}}" class="logo-light">
                        <img src="/images/logo-light.png" height="60" alt="logo light">
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
                            {{-- <a href="{{ route('second', ['auth', 'password'])}}"
                               class="float-end text-muted text-unline-dashed ms-1">Reset
                                password</a> --}}
                            <label class="form-label" for="example-password">Password</label>
                            <input type="password" id="example-password"
                                   class="form-control bg-light bg-opacity-50 border-light py-2"
                                   placeholder="Enter your password" name="password">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="checkbox-signin">
                                <label class="form-check-label" for="checkbox-signin">Remember me</label>
                            </div>
                        </div>

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
