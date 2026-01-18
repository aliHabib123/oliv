<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Registration Successful!</h1>
                <p class="text-gray-600">Your account has been created successfully</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Login Credentials</h2>

                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Company:</label>
                        <p class="text-gray-900 font-semibold">{{ $tenant->name }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Email:</label>
                        <p class="text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">{{ $email }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Password:</label>
                        <p class="text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">{{ $password }}</p>
                    </div>
                </div>

                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-3">
                    <p class="text-sm text-yellow-800">
                        <strong>⚠️ Important:</strong> Please save these credentials securely. For security reasons, this password will not be shown again.
                    </p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Next Steps:</h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Save your login credentials in a secure location</li>
                    <li>Access your admin dashboard using the link below</li>
                    <li>Complete your profile setup</li>
                    <li>Connect your Shopify store</li>
                    <li>Explore available add-ons to enhance your plan</li>
                </ol>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('filament.admin.auth.login') }}"
                    class="flex-1 bg-blue-600 text-white text-center py-3 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
                    Login to Dashboard
                </a>
                <a href="{{ route('subscriptions.index') }}"
                    class="flex-1 bg-gray-300 text-gray-700 text-center py-3 px-4 rounded-lg hover:bg-gray-400 transition font-semibold">
                    Back to Packages
                </a>
            </div>

            <div class="mt-6 text-center text-sm text-gray-600">
                <p>Need help? Contact our support team at <a href="mailto:support@example.com" class="text-blue-600 hover:underline">support@example.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>
