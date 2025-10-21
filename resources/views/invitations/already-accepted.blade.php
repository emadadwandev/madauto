<x-guest-layout>
    <div class="text-center">
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Invitation Already Accepted</h2>

        <p class="text-gray-600 mb-6">
            This invitation has already been accepted and an account has been created.
        </p>

        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 text-left">
            <p class="text-sm text-green-700">
                If you've already created your account, you can log in using your email and password.
            </p>
        </div>

        <div class="flex flex-col space-y-3">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Go to Login
            </a>

            <a href="{{ route('password.request') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Forgot your password?
            </a>
        </div>
    </div>
</x-guest-layout>
