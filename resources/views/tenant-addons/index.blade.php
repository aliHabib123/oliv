<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add-ons - {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-blue-600 hover:underline">← Back to Dashboard</a>
            </div>

            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Available Add-ons</h1>
                <p class="text-lg text-gray-600">Enhance your {{ $tenant->name }} experience with these add-ons</p>
            </div>

            @if(session('success'))
                <div class="mb-8 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-8 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if($addons->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-600">No add-ons available at the moment.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($addons as $addon)
                        @php
                            $isSubscribed = in_array($addon->id, $activeAddons);
                        @endphp

                        <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 {{ $isSubscribed ? 'border-green-500' : 'border-gray-200' }}">
                            <div class="p-6">
                                @if($isSubscribed)
                                    <div class="mb-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Active
                                        </span>
                                    </div>
                                @endif

                                <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $addon->name }}</h3>

                                <div class="mb-4">
                                    <span class="text-3xl font-bold text-gray-900">${{ number_format($addon->price, 2) }}</span>
                                    <span class="text-gray-600">/month</span>
                                </div>

                                <p class="text-gray-600 mb-6">{{ $addon->description }}</p>

                                @if($isSubscribed)
                                    <form action="{{ route('tenant-addons.unsubscribe', $addon) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Are you sure you want to unsubscribe from this add-on?')"
                                            class="w-full bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 transition font-semibold">
                                            Unsubscribe
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('tenant-addons.subscribe', $addon) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
                                            Subscribe (Dummy Payment)
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
