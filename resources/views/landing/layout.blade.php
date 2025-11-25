<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Careem-Loyverse Integration') }} - @yield('title', 'Automate Your Restaurant Orders')</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('description', 'Seamlessly sync your Careem Now orders to Loyverse POS. Save time, reduce errors, and grow your restaurant business.')">
    <meta name="keywords" content="Careem, Loyverse, POS, Restaurant, Order Management, Integration, Automation">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name') }} - @yield('title', 'Automate Your Restaurant Orders')">
    <meta property="og:description" content="@yield('description', 'Seamlessly sync your Careem Now orders to Loyverse POS.')">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ config('app.name') }} - @yield('title')">
    <meta property="twitter:description" content="@yield('description')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed w-full bg-white/95 backdrop-blur-sm shadow-sm z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('landing.index') }}" class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo-small.png') }}" alt="{{ config('app.name') }}" class="w-10 h-10">
                        <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'CareemSync') }}</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing.index') }}#features" class="text-gray-600 hover:text-indigo-600 transition-colors">Features</a>
                    <a href="{{ route('landing.index') }}#how-it-works" class="text-gray-600 hover:text-indigo-600 transition-colors">How It Works</a>
                    <a href="{{ route('landing.pricing') }}" class="text-gray-600 hover:text-indigo-600 transition-colors">Pricing</a>
                    <a href="{{ route('landing.index') }}#faq" class="text-gray-600 hover:text-indigo-600 transition-colors">FAQ</a>

                    @auth
                        @if(auth()->user()->tenant)
                            <a href="http://{{ auth()->user()->tenant->subdomain }}.{{ config('app.domain') }}:{{ request()->getPort() }}/dashboard" class="text-gray-600 hover:text-indigo-600 transition-colors">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600 transition-colors">Login</a>
                        <a href="{{ route('landing.register') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md hover:shadow-lg">
                            Start Free Trial
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="{{ route('landing.index') }}#features" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">Features</a>
                <a href="{{ route('landing.index') }}#how-it-works" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">How It Works</a>
                <a href="{{ route('landing.pricing') }}" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">Pricing</a>
                <a href="{{ route('landing.index') }}#faq" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">FAQ</a>

                @auth
                    @if(auth()->user()->tenant)
                        <a href="http://{{ auth()->user()->tenant->subdomain }}.{{ config('app.domain') }}:{{ request()->getPort() }}/dashboard" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">Dashboard</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-md">Login</a>
                    <a href="{{ route('landing.register') }}" class="block px-3 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-md text-center">Start Free Trial</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="{{ asset('images/logo-small.png') }}" alt="{{ config('app.name') }}" class="w-10 h-10">
                        <span class="text-xl font-bold text-white">{{ config('app.name', 'CareemSync') }}</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Seamlessly sync your Careem Now orders to Loyverse POS. Automate your restaurant operations and focus on what matters most - serving great food.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h3 class="text-white font-semibold mb-4">Product</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('landing.index') }}#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="{{ route('landing.pricing') }}" class="text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="{{ route('landing.index') }}#how-it-works" class="text-gray-400 hover:text-white transition-colors">How It Works</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Integrations</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-white font-semibold mb-4">Company</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- AOS (Animate On Scroll) Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>

    <!-- Alpine.js for mobile menu -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('navigation', () => ({
                mobileMenuOpen: false
            }))
        })
    </script>

    @stack('scripts')
</body>
</html>
