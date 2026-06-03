<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials/title-meta', ['title' => $title])
    @include('layouts.partials/head-css')
</head>

<body>

<div class="wrapper">

    @include("layouts.partials/topbar")
    @include("layouts.partials/main-nav")

    <div class="page-content">

        <div class="container-fluid">

            @include("layouts.partials/page-title",['title' => $title,'subTitle' => $subTitle])

            @yield('content')

        </div>

        @include("layouts.partials/footer")

        @yield('modal')
    </div>

</div>

@include("layouts.partials/right-sidebar")
@include('layouts.partials/footer-scripts')

</body>

</html>
