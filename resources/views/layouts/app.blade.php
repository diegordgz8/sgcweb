<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta name="csrf-token" content="{{ csrf_token() }}">

   <title>{{ config('app.name', 'SGC Web') }}</title>

   <!-- Fonts -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

   <!-- Scripts -->
   @vite(['resources/css/app.css', 'resources/js/app.js'])

   <!-- Styles -->
   <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
</head>

<body class="font-sans antialiased">
   <x-banner />

   <div class="min-h-screen bg-gray-100">
      @livewire('navigation-menu')

      <!-- Page Heading -->
      @if (isset($header))
         <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
               {{ $header }}
            </div>
         </header>
      @endif

      <!-- Page Content -->
      <main>
         {{ $slot }}
      </main>
   </div>

   @stack('modals')

   @stack('js')
</body>

</html>
