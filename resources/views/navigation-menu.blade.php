@php
$nav_links = [
    [
        'name' => 'Cierre de mes',
        'route' => route('cierre-mes.index'),
        'active' => request()->routeIs('cierre-mes.index'),
        'can' => 'cierre-mes.index',
    ],
    [
        'name' => 'Gastos',
        'route' => route('gasto.index'),
        'active' => request()->routeIs('gasto.index'),
        'can' => 'gasto.index',
    ],
    [
        'name' => 'Fondos',
        'route' => route('fondo.index'),
        'active' => request()->routeIs('fondo.index'),
        'can' => 'fondo.index',
    ],
    [
        'name' => 'Asambleas',
        'route' => route('asamblea.index'),
        'active' => request()->routeIs('asamblea.index'),
        'can' => 'asamblea.index',
    ],
    [
        'name' => 'Proveedores',
        'route' => route('proveedor.index'),
        'active' => request()->routeIs('proveedor.index'),
        'can' => 'proveedor.index',
    ],
    [
        'name' => 'Visita',
        'route' => route('visita.index'),
        'active' => request()->routeIs('visita.index'),
        'can' => 'visita.index',
    ],
];

$linksDropdown = [
    [
        'name' => 'Pagos del condominio',
        'route' => route('pago.create'),
        'active' => request()->routeIs('pago.create'),
        'can' => 'pago-condominio.create',
    ],
    [
        'name' => 'Notificar pago',
        'route' => route('pago-propietario.create'),
        'active' => request()->routeIs('pago-propietario.create'),
        'can' => 'pago-propietario.create',
    ],
    [
        'name' => 'Confirmar pagos',
        'route' => route('pago.confirmar'),
        'active' => request()->routeIs('pago.confirmar'),
        'can' => 'pago-propietario.confirmar',
    ],
];

$dropCount = 0;

foreach ($linksDropdown as $item) {
    if (Auth::user()->can($item['can'])) {
        $dropCount++;
    }
}

@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <div class="rounded-full bg-blue-500 p-2 shadow-md">
                            <img src="{{ asset('img/logo/blanco.png') }}" alt="" class="block h-9 w-auto">
                        </div>
                    </a>
                </div>

                <!-- Título -->
                <div class="self-center pl-3">
                    <a href="{{ route('home') }}">
                        <h1 class="font-semibold text-xl">SGC Web</h1>
                    </a>
                </div>

                @auth
                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
                            Inicio
                        </x-nav-link>

                        @if ($dropCount)
                            <x-dropdown>

                                <x-slot name="trigger">
                                    <x-nav-link class="h-full cursor-pointer">
                                        Pagos
                                    </x-nav-link>
                                </x-slot>

                                <x-slot name="content">

                                    @foreach ($linksDropdown as $item)

                                        @can($item['can'])
                                            <x-dropdown-link href="{{ $item['route'] }}" :active="$item['active']">
                                                {{ $item['name'] }}
                                            </x-dropdown-link>
                                        @endcan

                                    @endforeach
                                </x-slot>

                            </x-dropdown>
                        @endif

                        @foreach ($nav_links as $item)

                            @can($item['can'])

                                <x-nav-link href="{{ $item['route'] }}" :active="$item['active']">
                                    {{ $item['name'] }}
                                </x-nav-link>

                            @endcan

                        @endforeach

                    </div>
                @endauth
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @auth
                    <!-- Teams Dropdown -->
                    @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                        <div class="ml-3 relative">
                            <x-dropdown align="right" width="60">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition">
                                            {{ Auth::user()->currentTeam->name }}

                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-60">
                                        <!-- Team Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Manage Team') }}
                                        </div>

                                        <!-- Team Settings -->
                                        <x-dropdown-link
                                            href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                            {{ __('Team Settings') }}
                                        </x-dropdown-link>

                                        @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                            <x-dropdown-link href="{{ route('teams.create') }}">
                                                {{ __('Create New Team') }}
                                            </x-dropdown-link>
                                        @endcan

                                        <div class="border-t border-gray-100"></div>

                                        <!-- Team Switcher -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Switch Teams') }}
                                        </div>

                                        @foreach (Auth::user()->allTeams() as $team)
                                            <x-switchable-team :team="$team" />
                                        @endforeach
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    <!-- Settings Dropdown -->
                    <div class="ml-3 relative">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                    <button
                                        class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                        <img class="h-8 w-8 rounded-full object-cover"
                                            src="{{ Auth::user()->profile_photo_url }}"
                                            alt="{{ Auth::user()->name }}" />
                                    </button>
                                @else
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                            {{ Auth::user()->name }}

                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                @endif
                            </x-slot>

                            <x-slot name="content">

                                @can('home')
                                    <x-dropdown-link href="{{ route('home') }}">
                                        {{ __('Panel de propietario') }}
                                    </x-dropdown-link>
                                @endcan

                                @can('admin.home')
                                    <x-dropdown-link href="{{ route('admin.home') }}">
                                        {{ __('Administración del condominio') }}
                                    </x-dropdown-link>
                                @endcan

                                <!-- Account Management -->
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Administrar cuenta') }}
                                </div>

                                <x-dropdown-link href="{{ route('profile.show') }}">
                                    {{ __('Perfil') }}
                                </x-dropdown-link>

                                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                    <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                        {{ __('API Tokens') }}
                                    </x-dropdown-link>
                                @endif

                                <div class="border-t border-gray-100"></div>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                                                                                                            this.closest('form').submit();">
                                        {{ __('Cerrar sesión') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Iniciar sesión</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Registrarse</a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        @auth
            <div class="pt-2 pb-3 space-y-1">
                @foreach ($linksDropdown as $item)

                    @can($item['can'])
                        <x-responsive-nav-link href="{{ $item['route'] }}" :active="$item['active']">
                            {{ $item['name'] }}
                        </x-responsive-nav-link>
                    @endcan

                @endforeach

                @foreach ($nav_links as $item)

                    @can($item['can'])
                        <x-responsive-nav-link href="{{ $item['route'] }}" :active="$item['active']">
                            {{ $item['name'] }}
                        </x-responsive-nav-link>
                    @endcan

                @endforeach
            </div>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="flex items-center px-4">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <div class="shrink-0 mr-3">
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}"
                                alt="{{ Auth::user()->name }}" />
                        </div>
                    @endif

                    <div>
                        <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">

                    @can('admin.home')
                        <x-responsive-nav-link href="{{ route('admin.home') }}"
                            :active="request()->routeIs('admin.home')">
                            {{ __('Administración del condominio') }}
                        </x-responsive-nav-link>
                    @endcan

                    <!-- Account Management -->
                    <x-responsive-nav-link href="{{ route('profile.show') }}"
                        :active="request()->routeIs('profile.show')">
                        {{ __('Perfil') }}
                    </x-responsive-nav-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-responsive-nav-link href="{{ route('api-tokens.index') }}"
                            :active="request()->routeIs('api-tokens.index')">
                            {{ __('API Tokens') }}
                        </x-responsive-nav-link>
                    @endif

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link href="{{ route('logout') }}" onclick="event.preventDefault();
                        this.closest('form').submit();">
                            {{ __('Cerrar sesión') }}
                        </x-responsive-nav-link>
                    </form>

                    <!-- Team Management -->
                    @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                        <div class="border-t border-gray-200"></div>

                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Manage Team') }}
                        </div>

                        <!-- Team Settings -->
                        <x-responsive-nav-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}"
                            :active="request()->routeIs('teams.show')">
                            {{ __('Team Settings') }}
                        </x-responsive-nav-link>

                        @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                            <x-responsive-nav-link href="{{ route('teams.create') }}"
                                :active="request()->routeIs('teams.create')">
                                {{ __('Create New Team') }}
                            </x-responsive-nav-link>
                        @endcan

                        <div class="border-t border-gray-200"></div>

                        <!-- Team Switcher -->
                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Switch Teams') }}
                        </div>

                        @foreach (Auth::user()->allTeams() as $team)
                            <x-switchable-team :team="$team" component="jet-responsive-nav-link" />
                        @endforeach
                    @endif
                </div>
            </div>
        @else
            <x-responsive-nav-link href="{{ route('login') }}">
                Iniciar sesión
            </x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('register') }}">
                Registrarse
            </x-responsive-nav-link>
        @endauth
    </div>
</nav>
