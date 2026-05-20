<header class="site-header">
    <input type="checkbox" id="site-nav-toggle" class="site-nav-checkbox">
    <div class="container navbar-wrap">
        <a href="{{ route('home') }}" class="brand" aria-label="GroceryGo home">
            <img src="{{ asset('assets/logo/GroceryGo-main.png') }}" alt="GroceryGo logo" class="brand-logo">
        </a>

        <label for="site-nav-toggle" class="menu-toggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <nav class="main-nav" aria-label="Primary">
            <div class="nav-primary">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                <a href="{{ route('shops.index') }}" @class(['active' => request()->routeIs('shops.index')]) @if(request()->routeIs('shops.index')) aria-current="page" @endif>Shops</a>
                <a href="{{ route('categories') }}" @class(['active' => request()->routeIs('categories', 'products.show')]) @if(request()->routeIs('categories', 'products.show')) aria-current="page" @endif>Categories</a>
                <a href="{{ route('about') }}" @class(['active' => request()->routeIs('about')]) @if(request()->routeIs('about')) aria-current="page" @endif>About Us</a>
            </div>
            <div class="nav-actions">
                <a href="{{ route('cart') }}" class="nav-baskets @if(request()->routeIs('cart')) active @endif" @if(request()->routeIs('cart')) aria-current="page" @endif>Baskets</a>
                @auth
                    <a href="{{ route('profile.index') }}" class="nav-baskets @if(request()->routeIs('profile.*')) active @endif" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Profile</a>
                    <a href="{{ route('orders.index') }}" class="nav-baskets @if(request()->routeIs('orders.*', 'checkout.*')) active @endif" @if(request()->routeIs('orders.*', 'checkout.*')) aria-current="page" @endif>Orders</a>
                    <a href="{{ route('invoices.index') }}" class="nav-baskets @if(request()->routeIs('invoices.*')) active @endif" @if(request()->routeIs('invoices.*')) aria-current="page" @endif>Invoices</a>
                    <form method="post" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-signup nav-login">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-signup nav-login">Login</a>
                @endauth
            </div>
        </nav>
    </div>
</header>
