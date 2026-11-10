@extends('layouts.app')

@section('title', 'Notifications')
@section('topbar-title', 'Notifications')

@section('content')
    @php
        $unread = auth()->user()->unreadNotifications()->count();
    @endphp

    <x-page-header title="Notifications" :subtitle="$unread > 0 ? $unread.' notification(s) non lue(s).' : 'Toutes vos notifications sont lues.'">
        <x-slot:actions>
            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('patch')
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all me-1"></i>Tout marquer comme lu</button>
                </form>
            @endif
            @if (auth()->user()->isSupervisor())
                <a href="{{ route('supervisor.notifications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-megaphone me-1"></i>Envoyer un message</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $notifications->total() }} notification(s)</span>
            <span class="text-muted text-tiny">Page {{ $notifications->currentPage() }} / {{ $notifications->lastPage() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($notifications->isEmpty())
                <x-empty-state icon="bi-bell-slash" title="Aucune notification" text="Vous serez informé des nouveaux exercices, quiz, corrections et échéances." />
            @else
                @foreach ($notifications as $notification)
                    <div class="d-flex gap-2 px-3 py-3 {{ ! $loop->last ? 'border-bottom' : '' }} {{ $notification->unread() ? 'bg-primary-soft' : '' }}">
                        <span class="stat-icon {{ $notification->unread() ? 'primary' : 'secondary' }}"><i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="small fw-semibold">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                    @if ($notification->unread())<span class="badge text-bg-danger ms-1">Nouveau</span>@endif
                                </div>
                                <span class="text-muted text-tiny">{{ $notification->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small text-muted mt-1">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @if (! empty($notification->data['url']))
                                    <a href="{{ route('notifications.read', $notification->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-right me-1"></i>Ouvrir
                                    </a>
                                @endif
                                @if ($notification->unread())
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf @method('patch')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2 me-1"></i>Marquer comme lu</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
