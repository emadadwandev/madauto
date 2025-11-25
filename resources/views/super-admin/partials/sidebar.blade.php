<div class="flex h-16 shrink-0 items-center">
    <img class="h-12 w-auto" src="{{ asset('images/logo-small.png') }}" alt="MAD Logo">
    <span class="ml-3 text-xl font-bold text-gray-900">Super Admin</span>
</div>

<nav class="flex flex-1 flex-col">
    <ul role="list" class="flex flex-1 flex-col gap-y-7">
        <li>
            <ul role="list" class="-mx-2 space-y-1">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('super-admin.dashboard') }}"
                       class="{{ request()->routeIs('super-admin.dashboard') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Dashboard
                    </a>
                </li>

                <!-- Tenants -->
                <li>
                    <a href="{{ route('super-admin.tenants.index') }}"
                       class="{{ request()->routeIs('super-admin.tenants.*') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                        </svg>
                        Tenants
                    </a>
                </li>

                <!-- Subscriptions -->
                <li>
                    <a href="{{ route('super-admin.subscriptions.index') }}"
                       class="{{ request()->routeIs('super-admin.subscriptions.*') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        Subscriptions
                    </a>
                </li>

                <!-- System -->
                <li>
                    <div x-data="{ open: {{ request()->routeIs('super-admin.system.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="{{ request()->routeIs('super-admin.system.*') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm leading-6 font-semibold">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            System
                            <svg :class="{'rotate-90': open}" class="ml-auto h-5 w-5 shrink-0 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <ul x-show="open" class="mt-1 px-2 space-y-1">
                            <li>
                                <a href="{{ route('super-admin.system.index') }}"
                                   class="{{ request()->routeIs('super-admin.system.index') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6">
                                    Health
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('super-admin.system.failed-jobs') }}"
                                   class="{{ request()->routeIs('super-admin.system.failed-jobs') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6">
                                    Failed Jobs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('super-admin.system.sync-logs') }}"
                                   class="{{ request()->routeIs('super-admin.system.sync-logs') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6">
                                    Sync Logs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('super-admin.system.webhook-logs') }}"
                                   class="{{ request()->routeIs('super-admin.system.webhook-logs') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6">
                                    Webhook Logs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('super-admin.system.logs') }}"
                                   class="{{ request()->routeIs('super-admin.system.logs') ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6">
                                    Application Logs
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </li>
    </ul>
</nav>
