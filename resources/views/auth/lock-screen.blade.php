@extends('layouts.auth', ['title' => 'Lock Screen'])

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

            <div class="text-center mb-2">
                <img class="rounded-circle avatar-lg img-thumbnail" src="/images/users/avatar-1.jpg" alt="avatar">
            </div>
            <h2 class="fw-bold text-uppercase text-center fs-18">Hi ! Gaston</h2>
            <p class="text-muted text-center mt-1 mb-4">Enter your password to access the admin.</p>

            <div class="px-4">
                <form action="{{ route('second', ['dashboards', 'analytics'])}}" class="authentication-form">
                    <div class="mb-3">
                        <label class="form-label visually-hidden" for="example-password">Password</label>
                        <input type="text" id="example-password" class="form-control bg-light bg-opacity-50 border-light py-2" placeholder="Enter your password">
                    </div>
                    <div class="mb-1 text-center d-grid">
                        <button class="btn btn-danger py-2" type="submit">Sign In</button>
                    </div>
                </form>
            </div> <!-- end col -->
        </div> <!-- end card-body -->
    </div> <!-- end card -->
    <p class="mb-0 text-center text-white">Not you? return <a href="{{ route('second', ['auth', 'login'])}}" class="text-reset text-unline-dashed fw-bold ms-1">Sign In</a></p>
</div> <!-- end col -->

@endsection
