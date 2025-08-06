<header class="">
     <div class="topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <!-- Menu Toggle Button -->
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu topbar-button">
                              <i class="ri-menu-2-line fs-24"></i>
                         </button>
                    </div>

                    <!-- App Search-->
                    {{-- <form class="app-search d-none d-md-block me-auto">
                         <div class="position-relative">
                              <input type="search" class="form-control border-0" placeholder="Search..." autocomplete="off" value="">
                              <i class="ri-search-line search-widget-icon"></i>
                         </div>
                    </form> --}}
               </div>

               <div class="d-flex align-items-center gap-1">
                    <!-- Theme Color (Light/Dark) -->
                    <div class="topbar-item">
                         <button type="button" class="topbar-button" id="light-dark-mode">
                              <i class="ri-moon-line fs-24 light-mode"></i>
                              <i class="ri-sun-line fs-24 dark-mode"></i>
                         </button>
                    </div>

                    <!-- Category -->
                    <div class="dropdown topbar-item d-none d-lg-flex">
                         <button type="button" class="topbar-button" data-toggle="fullscreen">
                              <i class="ri-fullscreen-line fs-24 fullscreen"></i>
                              <i class="ri-fullscreen-exit-line fs-24 quit-fullscreen"></i>
                         </button>
                    </div>

                    <!-- Notification -->
                    <div class="dropdown topbar-item">
                         <button type="button" class="topbar-button position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <i class="ri-notification-3-line fs-24"></i>
                              <span class="position-absolute topbar-badge fs-10 translate-middle badge bg-danger rounded-pill" id="unread-count">0<span class="visually-hidden">unread messages</span></span>
                         </button>
                         <div class="dropdown-menu py-0 dropdown-lg dropdown-menu-end" aria-labelledby="page-header-notifications-dropdown">
                              <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                                   <div class="row align-items-center">
                                        <div class="col">
                                             <h6 class="m-0 fs-16 fw-semibold">Notifications</h6>
                                        </div>
                                        <div class="col-auto">
                                             {{-- <a href="javascript:void(0);" class="text-dark text-decoration-underline" id="clear-notifications">
                                             <small>Clear All</small>
                                             </a> --}}
                                        </div>
                                   </div>
                              </div>
                              <div data-simplebar style="max-height: 280px;" id="notification-items">
                                   <!-- Notifications will be dynamically populated here -->
                              </div>
                              <div class="text-center py-3">
                                   <a href="{{ route('messages.index') }}" class="btn btn-primary btn-sm">View All Notifications <i class="ri-arrow-right-line ms-1"></i></a>
                              </div>
                         </div>
                         </div>
                    <!-- User -->
                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                   <img class="rounded-circle" width="32" src="{{ asset('images/users/boy.png') ?? asset('images/users/default.jpg') }}" alt="avatar-3">
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <h6 class="dropdown-header">Welcome {{ \Auth::user()->name }}!</h6>
                              <a class="dropdown-item" href="{{ route('second', ['auth', 'lock-screen'])}}">
                                   <iconify-icon icon="solar:lock-keyhole-broken" class="align-middle me-2 fs-18"></iconify-icon><span class="align-middle">Lock screen</span>
                              </a>

                              <div class="dropdown-divider my-1"></div>

                              <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                                   <iconify-icon icon="solar:logout-3-broken" class="align-middle me-2 fs-18"></iconify-icon><span class="align-middle">Logout</span>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</div>
</header>
<script>
    window.laravelRoutes = {
        unreadMessages: "{{ route('unread-messages') }}"
    };
</script>