@extends('landing.layout')

@section('title', 'Start Your Free Trial')
@section('description', 'Create your account and start automating your restaurant orders in minutes.')

@section('content')

<section class="py-20 bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <!-- Left Column: Benefits -->
            <div data-aos="fade-right">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6">
                    Start Your <span class="gradient-text">14-Day Free Trial</span>
                </h1>

                <p class="text-xl text-gray-600 mb-8">
                    Join hundreds of restaurants already automating their Careem orders. No credit card required.
                </p>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">14 Days Completely Free</h3>
                            <p class="text-gray-600">Full access to all features. No credit card required.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Setup in 10 Minutes</h3>
                            <p class="text-gray-600">Our wizard guides you through every step.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Cancel Anytime</h3>
                            <p class="text-gray-600">No long-term contracts. No penalties.</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial -->
                <div class="mt-8 p-6 bg-white rounded-xl shadow-md">
                    <div class="flex items-center mb-3">
                        <div class="flex text-yellow-400">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                    </div>
                    <p class="text-gray-700 italic mb-3">"Setup was incredibly easy. We were syncing orders within 10 minutes!"</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">SK</div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">Sarah Khan</div>
                            <div class="text-sm text-gray-600">Burger Hub</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Registration Form -->
            <div data-aos="fade-left">
                <div class="bg-white rounded-2xl shadow-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Create Your Account</h2>

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('landing.register.store') }}" x-data="registrationForm()">
                        @csrf

                        <!-- Personal Information -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                                placeholder="John Doe"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                                placeholder="john@example.com"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                                placeholder="••••••••"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="••••••••"
                            >
                        </div>

                        <!-- Company Information -->
                        <div class="mb-6 pt-6 border-t border-gray-200">
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Restaurant/Company Name</label>
                            <input
                                id="company_name"
                                name="company_name"
                                type="text"
                                value="{{ old('company_name') }}"
                                required
                                @input="generateSubdomain"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('company_name') border-red-500 @enderror"
                                placeholder="My Restaurant"
                            >
                            @error('company_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-2">
                                Subdomain
                                <span class="text-gray-500 font-normal">(Your dashboard URL)</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="subdomain"
                                    name="subdomain"
                                    type="text"
                                    x-model="subdomain"
                                    @input="checkSubdomain"
                                    value="{{ old('subdomain') }}"
                                    required
                                    class="block w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('subdomain') border-red-500 @enderror"
                                    placeholder="my-restaurant"
                                >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg x-show="checking" class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="available && !checking" class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="!available && !checking && subdomain.length > 0" class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                <span x-text="subdomainPreview"></span>
                            </p>
                            <p x-show="availabilityMessage" x-text="availabilityMessage" class="mt-1 text-sm" :class="available ? 'text-green-600' : 'text-red-600'"></p>
                            @error('subdomain')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Plan Selection -->
                        <div class="mb-6 pt-6 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Choose Your Plan</label>
                            <div class="space-y-3">
                                @foreach($plans as $plan)
                                    <label class="relative flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition {{ $selectedPlan === $plan->slug ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200' }}">
                                        <input
                                            type="radio"
                                            name="plan_id"
                                            value="{{ $plan->id }}"
                                            {{ (old('plan_id') == $plan->id) || ($selectedPlan === $plan->slug) || ($loop->index === 1) ? 'checked' : '' }}
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        >
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                                                <span class="text-lg font-bold text-gray-900">${{ number_format($plan->price, 0) }}/mo</span>
                                            </div>
                                            <p class="text-sm text-gray-600">{{ $plan->order_limit ? number_format($plan->order_limit) . ' orders' : 'Unlimited orders' }} • {{ $plan->location_limit === 0 ? 'Unlimited' : $plan->location_limit }} {{ $plan->location_limit === 1 ? 'location' : 'locations' }}</p>
                                        </div>
                                        @if($plan->slug === 'business')
                                            <span class="absolute -top-2 -right-2 bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded-full">Popular</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            @error('plan_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Terms -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox" required class="h-4 w-4 mt-1 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-600">
                                    I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-700">Terms of Service</a> and <a href="#" class="text-indigo-600 hover:text-indigo-700">Privacy Policy</a>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        >
                            Start Free Trial
                        </button>

                        <p class="mt-4 text-center text-sm text-gray-600">
                            Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">Log in</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
function registrationForm() {
    return {
        subdomain: '{{ old('subdomain') }}',
        available: false,
        checking: false,
        availabilityMessage: '',
        checkTimeout: null,

        get subdomainPreview() {
            if (this.subdomain) {
                return `Your dashboard will be at: ${this.subdomain}.{{ config('app.domain', 'yourapp.com') }}`;
            }
            return 'Enter a subdomain to see your URL';
        },

        generateSubdomain(event) {
            if (this.subdomain.length > 0) return; // Don't auto-generate if user has already typed

            const companyName = event.target.value;
            const subdomain = companyName
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')           // Replace spaces with hyphens
                .replace(/-+/g, '-')            // Replace multiple hyphens with single
                .replace(/^-|-$/g, '');         // Remove leading/trailing hyphens

            this.subdomain = subdomain;
            this.checkSubdomain();
        },

        checkSubdomain() {
            clearTimeout(this.checkTimeout);

            if (this.subdomain.length === 0) {
                this.availabilityMessage = '';
                return;
            }

            this.checking = true;
            this.availabilityMessage = '';

            this.checkTimeout = setTimeout(() => {
                fetch('/api/check-subdomain?subdomain=' + encodeURIComponent(this.subdomain))
                    .then(response => response.json())
                    .then(data => {
                        this.available = data.available;
                        this.availabilityMessage = data.message;
                        this.checking = false;
                    })
                    .catch(() => {
                        this.checking = false;
                    });
            }, 500); // Debounce 500ms
        }
    }
}
</script>
@endpush
