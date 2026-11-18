<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - YottoCode</title>
    @include('partials.yottocode-head')
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col" style="min-height: 100vh; margin: 0;">
    <header class="bg-white shadow-sm py-4" style="position: fixed; top: 0; left: 0; right: 0; z-index: 20; width: 100vw;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ asset('img/yottocode-logo.svg') }}" alt="YottoCode" class="h-8 mx-auto">
        </div>
    </header>

    <div class="flex-1 px-4 sm:px-6 lg:px-8" style="padding-top: 5rem; display: flex; flex-direction: column;">
        <div class="max-w-2xl mx-auto pt-8 flex-grow w-full">
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="text-center mb-8">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-6" style="background-color: rgba(42, 171, 238, 0.1);">
                        <svg class="h-12 w-12" style="color: #2aabee;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-3">Payment Successful!</h1>
                    <p class="text-lg text-gray-600 mb-2">Your subscription is now active</p>
                    <p class="text-sm text-gray-500">A confirmation email has been sent to your email address</p>
                </div>

                <div class="rounded-lg p-6 mb-6" style="background-color: rgba(42, 171, 238, 0.05); border: 1px solid rgba(42, 171, 238, 0.2);">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Next Steps:</h3>
                    <ol class="space-y-3 text-gray-700">
                        <li class="flex items-start">
                            <span class="font-bold mr-3" style="color: #2aabee;">1.</span>
                            <span>Connect your Shopify store so we can build your app</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-3" style="color: #2aabee;">2.</span>
                            <span>Confirm your branding and main categories</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-3" style="color: #2aabee;">3.</span>
                            <span>Add your store info and finish setup</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-3" style="color: #2aabee;">4.</span>
                            <span>Access your admin dashboard any time using your email and password</span>
                        </li>
                    </ol>
                </div>

                <div class="text-center">
                    <a href="{{ $onboardingUrl ?? '/admin/login' }}"
                        class="yc-btn inline-block w-full text-center py-4 px-6 transition font-semibold text-lg shadow-lg">
                        {{ isset($onboardingUrl) && $onboardingUrl ? 'Start setting up your app' : 'Login to Dashboard' }}
                    </a>
                </div>

                <div class="mt-8 text-center text-sm text-gray-600">
                    <p>Need help? Contact our support team at <a href="mailto:support@yottocode.com" class="transition" style="color: #2aabee;" onmouseover="this.style.color='#b3e6ff'" onmouseout="this.style.color='#2aabee'">support@yottocode.com</a></p>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-white py-4 mt-auto" style="width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0 text-sm">
                <div class="text-center md:text-left">
                    <p class="text-gray-300">&copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="transition" style="color: #2aabee;" onmouseover="this.style.color='#b3e6ff'" onmouseout="this.style.color='#2aabee'">YottoCode</a></p>
                </div>
                <div class="flex items-center space-x-2 text-gray-300">
                    <svg class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-xs">Secured payment powered by</span>
                    <svg class="h-5 w-auto" viewBox="0 0 60 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.5 3.5C11.5 1.85 12.85 0.5 14.5 0.5H45.5C47.15 0.5 48.5 1.85 48.5 3.5V21.5C48.5 23.15 47.15 24.5 45.5 24.5H14.5C12.85 24.5 11.5 23.15 11.5 21.5V3.5Z" fill="#635BFF"/>
                        <path d="M22.8 17.2C21.4 17.2 20.3 16.5 19.9 15.3L21.4 14.6C21.6 15.3 22.2 15.8 22.9 15.8C23.7 15.8 24.2 15.3 24.2 14.3V8.5H25.8V14.3C25.8 16.2 24.7 17.2 22.8 17.2ZM27.5 17V8.5H29.1V11.1H29.2C29.5 10.3 30.2 9.8 31.1 9.8C32.3 9.8 33.2 10.6 33.2 12V17H31.6V12.4C31.6 11.6 31.1 11.2 30.4 11.2C29.6 11.2 29.1 11.7 29.1 12.6V17H27.5ZM35.2 17V8.5H36.8V11.1H36.9C37.2 10.3 37.9 9.8 38.8 9.8C40 9.8 40.9 10.6 40.9 12V17H39.3V12.4C39.3 11.6 38.8 11.2 38.1 11.2C37.3 11.2 36.8 11.7 36.8 12.6V17H35.2Z" fill="white"/>
                    </svg>
                    <span class="font-semibold text-xs">Stripe</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
