
@include('partials.header')
@if (is_home())

@include('partials.marca')
@endif
<span class="pointer-sombra" style="z-index:60;"></span>
<div class="scrollContainer is-relative" style="background: transparent !important;z-index: 2;">

@if (is_home())
<div class="has-background-white" data-aos data-scroll data-scroll-speed="10" style="height: 100vh;width:100vw;position: fixed;z-index: -1;">
  <div class="padre-voltis" style="z-index: 1;opacity: 0.3;position: fixed;top: 0;width: 250vw;height: 330vh">
    <div class="bg-hero" style="opacity: 0.3;background: url({{home_url('/app/uploads/2021/04/05-1.png')}}) center center / cover no-repeat;"></div>
    <div class="bg-hero" style="transform: rotatez(180deg
      ) rotatey(180deg);opacity: 0.3;background: url({{home_url('/app/uploads/2021/04/05-1.png')}}) center center / cover no-repeat;"></div>
      <div class="bg-hero" style="opacity: 0.3;background: url({{home_url('/app/uploads/2021/04/05-1.png')}}) center center / cover no-repeat;"></div>
  </div>
</div>
@endif
<main class="main" style="background: transparent !important;">
  @yield('content')
</main>

@hasSection('sidebar')
<aside class="sidebar">
  @yield('sidebar')
</aside>
@endif

@include('partials.footer')

</div>