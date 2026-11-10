<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e4d6b">
    <title>@yield('title', 'Tableau de bord') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar d-none d-lg-flex">
            @include('partials.sidebar')
        </aside>

        <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="d-flex flex-column h-100">
                <div class="d-flex justify-content-end px-2 pt-2">
                    <button type="button" class="icon-btn" data-bs-dismiss="offcanvas" aria-label="Fermer le menu">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                @include('partials.sidebar', ['mobile' => true])
            </div>
        </div>

        <div class="app-main">
            <header class="topbar">
                <button class="icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Ouvrir le menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <span class="brand-mark" style="width:28px;height:28px;font-size:.9rem"><i class="bi bi-mortarboard-fill"></i></span>
                    <span class="topbar-title">{{ config('app.name') }}</span>
                </div>

                <div class="topbar-title d-none d-lg-block">
                    @yield('topbar-title', auth()->user()->isSupervisor() ? 'Espace superviseur' : 'Espace étudiant')
                </div>

                <div class="topbar-spacer"></div>

                @php
                    $layoutUnread = auth()->user()->unreadNotifications()->count();
                    $layoutNotifications = auth()->user()->notifications()->latest()->limit(5)->get();
                @endphp

                <div class="dropdown">
                    <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if ($layoutUnread > 0)
                            <span class="unread-dot"></span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width:330px;max-width:92vw">
                        <div class="d-flex align-items-center justify-content-between px-2 py-1">
                            <strong class="small">Notifications</strong>
                            @if ($layoutUnread > 0)
                                <span class="badge text-bg-danger">{{ $layoutUnread }}</span>
                            @endif
                        </div>
                        <div class="dropdown-divider"></div>
                        @forelse ($layoutNotifications as $notification)
                            <a class="dropdown-item py-2" href="{{ route('notifications.open', $notification->id) }}">
                                <div class="d-flex gap-2">
                                    <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }} text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                        <div class="text-muted" style="font-size:.76rem">{{ Str::limit($notification->data['message'] ?? '', 90) }}</div>
                                        <div class="text-muted text-tiny mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="px-3 py-3 text-muted small">Aucune notification.</div>
                        @endforelse
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small fw-semibold" href="{{ route('notifications.index') }}">Voir toutes les notifications</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none text-dark d-flex align-items-center gap-2 px-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ auth()->user()->initials() }}</span>
                        <span class="d-none d-sm-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down text-muted" style="font-size:.65rem"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-2 py-1">
                            <div class="small fw-semibold">{{ auth()->user()->name }}</div>
                            <div class="text-muted" style="font-size:.74rem">{{ auth()->user()->role->label() }}</div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Mon profil</a></li>
                        <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="app-content">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
