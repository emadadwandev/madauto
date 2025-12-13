<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="/dashboard">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link href="/dashboard" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('orders.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('orders.*')">
                        {{ __('Orders') }}
                    </x-nav-link>

                    <!-- Menu Management Dropdown -->
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard.modifiers.*') || request()->routeIs('dashboard.modifier-groups.*') || request()->routeIs('dashboard.menus.*') || request()->routeIs('dashboard.locations.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                    <div>{{ __('Menu Management') }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Locations') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Menus') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('dashboard.modifiers.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Modifiers') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Modifier Groups') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <!-- Careem Integration Dropdown -->
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard.careem-brands.*') || request()->routeIs('dashboard.careem-branches.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                    <div>{{ __('Careem') }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('dashboard.careem-brands.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Brands') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('dashboard.careem-branches.index', ['subdomain' => request()->route('subdomain')])">
                                    {{ __('Branches') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <x-nav-link :href="route('dashboard.team.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.team.*')">
                        {{ __('Team') }}
                    </x-nav-link>
                    <x-nav-link :href="route('product-mappings.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('product-mappings.*')">
                        {{ __('Product Mappings') }}
                    </x-nav-link>
                    <x-nav-link :href="route('sync-logs.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('sync-logs.*')">
                        {{ __('Sync Logs') }}
                    </x-nav-link>
                    <x-nav-link :href="route('dashboard.notifications.show', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.notifications.*')">
                        {{ __('Notifications') }}
                    </x-nav-link>
                    <x-nav-link :href="route('api-credentials.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('api-credentials.*')">
                        {{ __('Settings') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Impersonating Notice -->
            @if(session('impersonating_from'))
            <div class="hidden sm:flex sm:items-center sm:me-6">
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 py-2 rounded flex items-center">
                    <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium me-3">Impersonating</span>
                    <form method="POST" action="{{ route('super-admin.tenants.stop-impersonating') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm underline hover:no-underline">
                            Stop
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link href="/profile">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="/logout">
                            @csrf

                            <x-dropdown-link href="/logout"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="/dashboard" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('orders.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('orders.*')">
                {{ __('Orders') }}
            </x-responsive-nav-link>

            <!-- Menu Management (Mobile) -->
            <div class="border-t border-gray-200 pt-2 pb-2">
                <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu Management</div>
                <x-responsive-nav-link :href="route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.locations.*')">
                    {{ __('Locations') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.menus.*')">
                    {{ __('Menus') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard.modifiers.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.modifiers.*')">
                    {{ __('Modifiers') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.modifier-groups.*')">
                    {{ __('Modifier Groups') }}
                </x-responsive-nav-link>
            </div>

            <!-- Careem Integration (Mobile) -->
            <div class="border-t border-gray-200 pt-2 pb-2">
                <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Careem</div>
                <x-responsive-nav-link :href="route('dashboard.careem-brands.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.careem-brands.*')">
                    {{ __('Brands') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard.careem-branches.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.careem-branches.*')">
                    {{ __('Branches') }}
                </x-responsive-nav-link>
            </div>

            <x-responsive-nav-link :href="route('dashboard.team.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.team.*')">
                {{ __('Team') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('product-mappings.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('product-mappings.*')">
                {{ __('Product Mappings') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sync-logs.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('sync-logs.*')">
                {{ __('Sync Logs') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dashboard.notifications.show', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('dashboard.notifications.*')">
                {{ __('Notifications') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('api-credentials.index', ['subdomain' => request()->route('subdomain')])" :active="request()->routeIs('api-credentials.*')">
                {{ __('Settings') }}
            </x-responsive-nav-link>
        </div>

        <!-- Impersonating Notice (Mobile) -->
        @if(session('impersonating_from'))
        <div class="pt-2 pb-3 border-t border-gray-200">
            <div class="px-4">
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 py-2 rounded">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">Impersonating</span>
                        </div>
                        <form method="POST" action="{{ route('super-admin.tenants.stop-impersonating') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm underline hover:no-underline">
                                Stop
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link href="/profile">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="/logout">
                    @csrf

                    <x-responsive-nav-link href="/logout"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
