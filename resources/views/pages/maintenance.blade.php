@extends('layouts.auth', ['title' => 'Maintenance'])

@section('content')

<div class="col-xl-12">
    <div class="card auth-card">
        <div class="card-body p-0">
            <div class="row align-items-center g-0">
                <div class="col-lg-4 d-none d-lg-inline-block border-end">
                    <div class="auth-page-sidebar">
                        <img src="/images/maintenance.svg" alt="auth" class="img-fluid" />
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="p-4">
                        <div class="mx-auto mb-5 text-center auth-logo">
                            <a href="{{ route('second', ['dashboards', 'analytics'])}}" class="logo-dark">
                                <img src="/images/logo-dark.png" height="32" alt="logo dark">
                            </a>

                            <a href="{{ route('second', ['dashboards', 'analytics'])}}" class="logo-light">
                                <img src="/images/logo-light.png" height="28" alt="logo light">
                            </a>
                        </div>
                        <h2 class="fw-bold text-center lh-base">We are currently performing maintenance</h2>
                        <p class="text-muted text-center mt-1 mb-4">We're making the system more awesome.
                            We'll be back shortly.</p>

                        <div class="text-center">
                            <a href="#" class="btn btn-danger">Contact Us</a>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- end card-body -->
    </div> <!-- end card -->

</div> <!-- end col -->

@endsection