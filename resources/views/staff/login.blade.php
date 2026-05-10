@extends('layouts.staff')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-900" x-data="{ pin: '' }">
    <div class="w-full max-w-sm p-6">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-white mb-2">Login Staff</h1>
            <p class="text-gray-400">Introduceți codul PIN de 4 cifre</p>
        </div>

        <!-- PIN Display -->
        <div class="flex justify-center mb-8 space-x-4">
            <template x-for="i in 4">
                <div class="w-4 h-4 rounded-full border-2 transition-all duration-200"
                     :class="pin.length >= i ? 'bg-orange-500 border-orange-500' : 'border-gray-600 bg-gray-800'">
                </div>
            </template>
        </div>

        <!-- Hidden Form -->
        <form id="login-form" x-ref="form" method="POST" action="{{ route('staff.verify') }}">
            @csrf
            <input type="hidden" name="pin" :value="pin">
        </form>

        <!-- Error Message -->
        @if($errors->any())
            <div class="text-red-500 text-center mb-6 font-bold animate-pulse">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Keypad -->
        <div class="grid grid-cols-3 gap-6 mb-8">
            <template x-for="num in [1, 2, 3, 4, 5, 6, 7, 8, 9]">
                <button 
                    @click="if(pin.length < 4) pin += num.toString(); if(pin.length === 4) $nextTick(() => $refs.form.submit());"
                    class="h-20 w-20 rounded-full bg-gray-800 hover:bg-gray-700 text-white text-2xl font-bold transition flex items-center justify-center mx-auto shadow-lg"
                    x-text="num"
                ></button>
            </template>
            
            <div class="mx-auto"></div> <!-- Empty spacer -->
            
            <button 
                @click="if(pin.length < 4) pin += '0'; if(pin.length === 4) $nextTick(() => $refs.form.submit());"
                 class="h-20 w-20 rounded-full bg-gray-800 hover:bg-gray-700 text-white text-2xl font-bold transition flex items-center justify-center mx-auto shadow-lg"
            >0</button>
            
            <button 
                @click="pin = pin.slice(0, -1)"
                class="h-20 w-20 rounded-full bg-red-900/20 hover:bg-red-900/40 text-red-400 text-xl font-bold transition flex items-center justify-center mx-auto shadow-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l5.94-6.12a2 2 0 011.42-.58H21a2 2 0 012 2v10a2 2 0 01-2 2H10.38a2 2 0 01-1.42-.58L3 12z" />
                </svg>
            </button>
        </div>
        
    </div>
</div>
@endsection
