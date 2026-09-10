<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteSettings['site_name'] ?? 'Trendy Deal BD')</title>
    <meta name="description" content="@yield('meta_description', $siteSettings['site_subtitle'] ?? '')">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v=1.6.1">
    <style>:root{--brand:{{ $siteSettings['primary_color'] ?? '#00B957' }};--brand-dark:{{ $siteSettings['secondary_color'] ?? '#122B35' }};}</style>
    @stack('head')
    @if(($siteSettings['meta_browser_enabled'] ?? '0') === '1' && !empty($siteSettings['meta_pixel_id']))
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init','{{ $siteSettings['meta_pixel_id'] }}');
    </script>
    @endif
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <span><strong>হেল্প লাইন:</strong> {{ $siteSettings['help_line'] ?? '01712969880' }}</span>
        <a class="desktop-logo" href="{{ route('home') }}">
            @if(!empty($siteSettings['logo_path']))<img src="{{ asset('storage/'.$siteSettings['logo_path']) }}" alt="logo">@else<span class="logo-mark">T</span><b>{{ $siteSettings['site_name'] ?? 'Trendy Deal BD' }}</b>@endif
        </a>
        <div class="top-icons"><a href="{{ route('shop') }}" aria-label="search">⌕</a><a href="{{ route('cart') }}" aria-label="cart">🛒 <span class="badge">{{ collect(session('cart',[]))->sum('quantity') }}</span></a></div>
    </div>
</header>
<nav class="nav desktop-nav"><div class="container nav-inner">
    <a href="{{ route('home') }}">হোম</a><a href="{{ route('shop') }}">শপ</a>
    @foreach(($navCategories ?? collect())->take(5) as $category)<a href="{{ $category->subcategories->first() ? route('subcategory.show',$category->subcategories->first()->slug) : route('shop') }}">{{ $category->name_bn }}</a>@endforeach
    <a href="#footer">যোগাযোগ</a>
</div></nav>
<header class="mobile-head"><button class="icon-btn" type="button" onclick="document.body.classList.toggle('menu-open')">☰</button><a href="{{ route('home') }}" class="mobile-logo">@if(!empty($siteSettings['logo_path']))<img src="{{ asset('storage/'.$siteSettings['logo_path']) }}">@else<b>{{ $siteSettings['site_name'] ?? 'Trendy Deal BD' }}</b>@endif</a><a href="{{ route('cart') }}">🛒<span class="badge">{{ collect(session('cart',[]))->sum('quantity') }}</span></a></header>
<div class="mobile-menu"><a href="{{ route('home') }}">হোম</a><a href="{{ route('shop') }}">শপ</a>@foreach(($navCategories ?? collect()) as $category)<span class="menu-label">{{ $category->name_bn }}</span>@foreach($category->subcategories as $sub)<a href="{{ route('subcategory.show',$sub->slug) }}">— {{ $sub->name_bn }}</a>@endforeach @endforeach</div>

<main>@yield('content')</main>

<footer id="footer" class="footer"><div class="container footer-grid">
    <div><h3>যোগাযোগ</h3><p>{{ $siteSettings['address'] ?? '' }}</p><p><strong>ফোন:</strong> {{ $siteSettings['phone'] ?? '' }}</p><p><strong>ইমেইল:</strong> {{ $siteSettings['email'] ?? '' }}</p><div class="socials">@if(!empty($siteSettings['facebook_url']))<a href="{{ $siteSettings['facebook_url'] }}" target="_blank">Facebook</a>@endif @if(!empty($siteSettings['instagram_url']))<a href="{{ $siteSettings['instagram_url'] }}" target="_blank">Instagram</a>@endif</div></div>
    @php
      $footerGroups=[
        [$siteSettings['footer_shop_title']??'শপ করুন',$siteSettings['footer_shop_links']??''],
        [$siteSettings['footer_useful_title']??'প্রয়োজনীয় লিংক',$siteSettings['footer_useful_links']??''],
        [$siteSettings['footer_service_title']??'কাস্টমার সার্ভিস',$siteSettings['footer_service_links']??'']
      ];
    @endphp
    @foreach($footerGroups as [$title,$links])<div><h3>{{ $title }}</h3>@foreach(preg_split('/\r\n|\r|\n/',$links) as $line)@php $parts=explode('|',$line,2); @endphp @if(trim($parts[0]??'')!=='')<a class="footer-link" href="{{ trim($parts[1]??'#') }}">{{ trim($parts[0]) }}</a>@endif @endforeach</div>@endforeach
</div><div class="copyright">{{ $siteSettings['footer_copyright'] ?? '© Trendy Deal BD' }}</div></footer>

<nav class="mobile-bottom"><a href="{{ route('home') }}"><span>⌂</span>হোম</a><a href="{{ route('shop') }}"><span>⌕</span>সার্চ</a><a href="{{ route('shop') }}"><span>▦</span>শপ</a><a href="https://wa.me/{{ preg_replace('/\D/','',$siteSettings['phone']??'') }}" target="_blank"><span>◉</span>WhatsApp</a><a href="{{ route('cart') }}"><span>🛒</span>কার্ট</a></nav>

<div class="quick-checkout-modal" id="quickCheckoutModal" aria-hidden="true">
    <button type="button" class="quick-checkout-backdrop" data-quick-checkout-close aria-label="চেকআউট বন্ধ করুন"></button>
    <section class="quick-checkout-sheet" role="dialog" aria-modal="true" aria-label="চেকআউট">
        <div class="quick-checkout-loading" data-quick-checkout-loading>চেকআউট লোড হচ্ছে...</div>
        <div data-quick-checkout-content></div>
    </section>
</div>

<script>
window.shopMeta={enabled:@json(($siteSettings['meta_browser_enabled']??'0')==='1' && !empty($siteSettings['meta_pixel_id'])), endpoint:@json(route('meta.event')), csrf:@json(csrf_token())};
window.storeRoutes={quickCheckout:@json(route('checkout.quick'))};
</script>
<script src="{{ asset('assets/app.js') }}?v=1.6.1" defer></script>
@stack('scripts')
</body>
</html>
