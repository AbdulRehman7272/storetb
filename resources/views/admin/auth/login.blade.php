@php use App\Support\StoreSettings; @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - {{ StoreSettings::get('store_name', 'TBrand') }}</title>
    <link rel="stylesheet" href="{{ asset('admin-assets/css/bootstrap.css') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="login-screen">
    <form method="post" action="{{ route('admin.login.store') }}" class="login-card">
        @csrf
        <img src="{{ asset(StoreSettings::get('main_logo', 'brand/tbrand/01_Main_Horizontal_Black.png')) }}" alt="TBrand">
        <h1>Admin Login</h1>
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        <input name="username" type="text" placeholder="Username" value="{{ old('username', 'admin') }}" autocomplete="username" required>
        <input name="password" type="password" placeholder="Password" required>
        <label><input type="checkbox" name="remember"> Remember me</label>
        <button class="btn">Login</button>
    </form>
</body>
</html>
