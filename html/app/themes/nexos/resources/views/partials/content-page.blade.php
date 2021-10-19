@if(!is_user_logged_in() && is_page('mi-cuenta' ) || !is_user_logged_in() && is_page('my-account'))
<div class="column is-paddingless is-half">
    @include('components.passport')
</div>
@endif
@php(the_content())
{!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']) !!}