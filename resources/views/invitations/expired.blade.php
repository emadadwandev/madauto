<x-guest-layout>
    <div class="text-center">
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Invitation Expired</h2>

        <p class="text-gray-600 mb-6">
            Unfortunately, this invitation has expired. Invitations are valid for 7 days after being sent.
        </p>

        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-red-800 mb-2">
                <strong>Invitation Details:</strong>
            </p>
            <p class="text-sm text-red-700">
                <strong>Organization:</strong> {{ $invitation->tenant->name }}<br>
                <strong>Role:</strong> {{ $invitation->role->display_name }}<br>
                <strong>Expired:</strong> {{ $invitation->expires_at->diffForHumans() }}
            </p>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-left">
            <p class="text-sm text-blue-700">
                <strong>What can you do?</strong>
            </p>
            <p class="text-sm text-blue-600 mt-2">
                Please contact <strong>{{ $invitation->invitedBy->name }}</strong> at
                <a href="mailto:{{ $invitation->invitedBy->email }}" class="text-blue-800 underline">
                    {{ $invitation->invitedBy->email }}
                </a>
                to request a new invitation.
            </p>
        </div>

        <div class="mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Already have an account? Log in
            </a>
        </div>
    </div>
</x-guest-layout>
