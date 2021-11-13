@extends('layouts.app')

@section('title', '123Movies.io Clone')

@section('content')
    <div id="main" class="">
        <div class="container">
            <div class="main-content">
                <div class="movies-list-wrap mlw-topview mt20">
                    <!--search movies-->
                    <div class="movies-list-wrap mlw-latestmovie">
                        <div class="ml-title"> <span class="pull-left">IMDb Top 250 <i class="fa fa-chevron-right ml10"></i></span> <a href="https://123movies.film/movie/filter/movie" class="pull-right cat-more">View more »</a>
                            <div class="clearfix"></div>
                        </div>
                        <div class="movies-list movies-list-full tab-pane in fade active">

                            @foreach($movies as $movie) 
                            <div data-movie-id="{{ $movie->id }}" class="ml-item">
                                <a href="/watch/{{$movie->id}}/{{str_slug($movie->title, '-')}}" data-url="/imdb/info" class="ml-mask jt" title="{{$movie->title }} ({{$movie->year}})"> <span class="mli-quality">DVD</span> <img data-original="/cdn/posters/{{$movie->id}}.jpg" class="lazy thumb mli-thumb" alt="{{$movie->title }} ({{$movie->year}})"> <span class="mli-info"><h2>{{$movie->title }} ({{$movie->year}})</h2></span> </a>
                            </div>
                            @endforeach

                            <div class="clearfix"></div>
                        </div>
                    </div>
                    <!--/search movies-->
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        if (!jQuery.browser.mobile) {
            $('.jt').qtip({
                content: {
                    text: function(event, api) {
                        $.ajax({
                            url: api.elements.target.attr('data-url'),
                            type: 'GET',
                            success: function(data, status) {
                                api.set('content.text', data);
                            }
                        });
                    },
                    title: function(event, api) {
                        return $(this).attr('title');
                    }
                },
                position: {
                    my: 'top left',
                    at: 'top right',
                    viewport: $(window),
                    effect: false,
                    target: 'mouse',
                    adjust: {
                        mouse: false
                    },
                    show: {
                        effect: false
                    }
                },
                hide: {
                    fixed: true
                },
                style: {
                    classes: 'qtip-light qtip-bootstrap',
                    width: 320
                }
            });
        }
        $("img.lazy").lazyload({
            effect: "fadeIn"
        });
    </script>
@endsection
