<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@isset($title){{ $title }} — @endisset{{ config('app.name', 'PortGuard') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|instrument-serif:400&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript><link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|instrument-serif:400&display=swap" rel="stylesheet" /></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="pg-app-body"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('pg_sidebar_collapsed') === '1',
        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('pg_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
        }
    }"
    @keydown.escape.window="sidebarOpen = false"
>
    <div class="pg-app-shell" :class="{ 'is-collapsed': sidebarCollapsed }">
        <div
            class="pg-sidebar-backdrop"
            :class="{ 'is-visible': sidebarOpen }"
            @click="sidebarOpen = false"
            x-cloak
        ></div>

        @include('layouts.sidebar')

        <div class="pg-main">
            <header class="pg-topbar">
                <button
                    type="button"
                    class="pg-menu-toggle"
                    @click="sidebarOpen = true"
                    aria-label="Menüyü aç"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="pg-collapse-toggle"
                    @click="toggleSidebarCollapse()"
                    :aria-label="sidebarCollapsed ? 'Menüyü genişlet' : 'Menüyü daralt'"
                    :title="sidebarCollapsed ? 'Genişlet' : 'Daralt'"
                >
                    <span aria-hidden="true" x-text="sidebarCollapsed ? '»' : '«'"></span>
                </button>

                <div class="pg-topbar-title">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1>Panel</h1>
                    @endisset
                </div>

                <div class="pg-topbar-right">
                    <span class="pg-topbar-user">{{ Auth::user()->name }}</span>
                </div>
            </header>

            <main class="pg-content">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
