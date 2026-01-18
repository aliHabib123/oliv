<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Package</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Package</h1>
                <p class="text-lg text-gray-600">Select the perfect plan for your business</p>
            </div>

            @if($errors->any())
                <div class="mb-8 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                @foreach($packages as $package)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-200 hover:border-blue-500 transition">
                        <div class="p-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ $package->name }}</h3>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-gray-900">${{ number_format($package->price, 2) }}</span>
                                <span class="text-gray-600">/month</span>
                            </div>
                            <p class="text-gray-600 mb-6">{{ $package->description }}</p>

                            @if($package->features)
                                <ul class="space-y-3 mb-8">
                                    @foreach($package->features as $feature => $description)
                                        <li class="flex items-start">
                                            <svg class="h-6 w-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-gray-700">{{ $feature }}: {{ $description }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <button
                                onclick="selectPackage({{ $package->id }}, '{{ $package->name }}')"
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Select Package
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="signup-form" class="hidden max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Complete Your Registration</h2>
                <p class="text-gray-600 mb-6">Selected Package: <span id="selected-package" class="font-semibold"></span></p>

                <form action="{{ route('subscriptions.subscribe') }}" method="POST">
                    @csrf
                    <input type="hidden" name="package_id" id="package_id">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                            <input type="text" name="company_name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('company_name') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('email') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person *</label>
                            <input type="text" name="contact_person" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('contact_person') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="{{ old('phone') }}">
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="submit"
                                class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Complete Registration (Dummy Payment)
                            </button>
                            <button type="button" onclick="cancelSelection()"
                                class="flex-1 bg-gray-300 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-400 transition font-semibold">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectPackage(packageId, packageName) {
            document.getElementById('package_id').value = packageId;
            document.getElementById('selected-package').textContent = packageName;
            document.getElementById('signup-form').classList.remove('hidden');
            document.getElementById('signup-form').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelSelection() {
            document.getElementById('signup-form').classList.add('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
