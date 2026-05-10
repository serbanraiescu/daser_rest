<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate License - RestaurantOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Activate License</h1>
            <p class="text-gray-500 text-sm mt-1">Please enter your license key to continue.</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        
        @if(isset($status) && $status->message)
             <div class="bg-yellow-50 text-yellow-700 p-3 rounded mb-4 text-sm">
                Status: {{ ucfirst($status->status) }} <br>
                {{ $status->message }}
            </div>
        @endif

        <form action="{{ url('/setup/license/activate') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Fingerprint</label>
                <input type="text" value="{{ $fingerprint }}" disabled class="w-full bg-gray-200 text-gray-600 border rounded py-2 px-3 leading-tight focus:outline-none focus:shadow-outline">
                <small class="text-xs text-gray-400">This domain is bound to your license.</small>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="license_key">
                    License Key
                </label>
                <input name="license_key" id="license_key" type="text" placeholder="ENTER-LICENSE-KEY" required
                    class="w-full border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
            </div>

            <div class="flex items-center justify-between">
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150" type="submit">
                    Activate Product
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} RestaurantOS. All rights reserved.
        </div>
    </div>

</body>
</html>
