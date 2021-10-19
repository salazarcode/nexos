@extends('layouts.app')

@section('content')

  <style>
    .mla_chart_toolbar_actions{
      display:none;
    }
    .orgchart>table {
      transform: rotate(-90deg) !important;
    }
    .node.active {
      transform: rotate(90deg) !important;
    }
    .node .title {
      height: 120px !important;
      width: 120px !important;
      display: flex !important;
      border-radius: 50% !important;
      align-items: center !important;
      justify-content: center !important;
      left: 50% !important;
      position: relative !important;
      flex-direction: column !important;
      transform: translateX(-50%) !important;
      margin:0;
    }

    .node .content {
      border-radius: 5px;
      top: -36px;
      position: relative;
      font-weight: bold;
    }
  </style>

  @include('partials.page-header')
  <div class="container has-padding-top-50">
    <div class="content px-6 py-6 box" data-scroll data-scroll-speed="1" data-aos>
      @while(have_posts()) @php(the_post())
        @includeFirst(['partials.content-page', 'partials.content'])
      @endwhile
    </div>
  </div>
@endsection
