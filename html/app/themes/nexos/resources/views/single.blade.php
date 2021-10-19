@extends('layouts.app')

@section('content')
  @include('partials.page-header')
  <div class="container has-padding-top-50">
    <div class="content px-6 py-6 box" data-scroll data-scroll-speed="1" data-aos>
      @while(have_posts()) @php(the_post())
        @includeFirst(['partials.content-single-' . get_post_type(), 'partials.content-single'])
      @endwhile
    </div>
  </div>
@endsection
