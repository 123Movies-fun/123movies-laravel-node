@extends('layouts.app')

@section('title', '123Movies.io Clone')

@section('content')
    <div id="main" class="">
        <div class="container">
            <div class="top-content">
                <!-- slider -->
                <div id="slider">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide" style="background-image: url(/images/maxresdefault.jpg);">
                            <a href="https://123moviestoday.com/film/split-2017.24658/" class="slide-link" title="Split (2017)"></a> <span class="slide-caption"> <h2>Split (2017)</h2> <p class="sc-desc">Kevin, a man with at least 23 different personalities, is compelled to abduct three teenage girls. As they are held captive, a final personality - "The Beast" - begins to materialize.</p> <div class="slide-caption-info"> <div class="block"><strong>Genre:</strong> <a href="/genre/horror" title="Horror">Horror</a>, <a href="/genre/thriller" title="Thriller">Thriller</a> </div> <div class="block"><strong>Duration:</strong> min</div> <div class="block"><strong>Release:</strong> 2017</div> <div class="block"><strong>IMDb:</strong> 7.5</div> </div> <a href="https://123moviestoday.com/film/split-2017.24658/watching.html"> <div class="btn btn-successful mt20">Watching</div> </a> </span> </div>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="clearfix"></div>
                </div>
                <!--/slider -->
                <!--top news-->
                <div id="top-news">
                    <div class="top-news">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="active"><a href="#tn-news" role="tab" data-toggle="tab">LATEST NEWS</a>
                            </li>
                            <li><a href="#tn-notice" role="tab" data-toggle="tab">NOTICE</a>
                            </li>
                        </ul>
                        <div class="top-news-content">
                            <div class="tab-content">
                                <div role="tabpanel" class="tab-pane in fade active" id="tn-news">
                                    <ul class="tn-news">
                                        <li>
                                            <a href="#" title="Review: ‘Storks,’ an Uneasy Return to Delivering Babies" class="thumb news-thumb" style="background-image:url(/images/23storks-master768.jpg);"></a>
                                            <div class="tnc-info">
                                                <h4> <a href="https://123moviestoday.com/articles/view/review-storks--an-uneasy-return-to-delivering-babies/5" title="Review: ‘Storks,’ an Uneasy Return to Delivering Babies">Review: ‘Storks,’ an Uneasy Return to Delivering Babies</a></h4> </div>
                                            <div class="clearfix"></div>
                                        </li>
 
                                        <li class="view-more"> <a href="https://123moviestoday.com/articles/news"> View more <i class="fa fa-chevron-circle-right"></i></a> </li>
                                    </ul>
                                </div>
                                <div role="tabpanel" class="tab-pane fade" id="tn-notice">
                                    <div class="tnc-apps"> <a href="#" class="tnca-block ios"><i class="fa fa-apple"></i><span>123movies</span> for Apple iOs</a> <a href="#" class="tnca-block android"><i class="fa fa-android"></i><span>123movies</span> for Android</a> </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/top news-->
                <div class="clearfix"></div>
            </div>
            <!--social home-->
            <div class="social-home">
                <div class="sh-like">
                    <div class="fb-like" data-href="http://facebook.com/123movies.to" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                </div>
                <div class="addthis_native_toolbox"></div> <span class="sh-text">Like and Share our website to support us.</span>
                <div class="clearfix"></div>
            </div>

            <div class="main-content">
                <div class="movies-list-wrap mlw-topview mt20">
                    <div class="ml-title"> <span class="pull-left">Suggestion <i class="fa fa-chevron-right ml10"></i></span> <a href="https://123moviestoday.com/movie/filter/all" class="pull-right cat-more">View more »</a>
                        <ul role="tablist" class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" role="tab" href="#movie-featured" aria-expanded="false">Featured</a>
                            </li>
                            <li><a onclick="ajaxContentBox('topview-today')" data-toggle="tab" role="tab" href="#topview-today" aria-expanded="false">Top viewed today</a>
                            </li>
                            <li><a onclick="ajaxContentBox('top-favorite')" data-toggle="tab" role="tab" href="#top-favorite" aria-expanded="false">Most Favorite</a> </li>
                            <li><a onclick="ajaxContentBox('top-rating')" data-toggle="tab" role="tab" href="#top-rating" aria-expanded="false">Top Rating</a>
                            </li>
                            <li><a onclick="ajaxContentBox('top-imdb')" data-toggle="tab" role="tab" href="#top-imdb" aria-expanded="false">Top IMDb</a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="tab-content">
                        <div id="movie-featured" class="movies-list movies-list-full tab-pane in fade active" style="display: none;">
                            <div data-movie-id="17088" class="ml-item">
                                <a href="https://123moviestoday.com/film/split-2017.24658/" data-url="https://123moviestoday.com/ajax/movie_load_info/17088" class="ml-mask jt" title="Split (2017)"> <span class="mli-quality">HD</span> <img data-original="https://img.123movies.film/crop/215/310/media/images/170206_043709/1.jpg" class="lazy thumb mli-thumb" alt="Split (2017)"> <span class="mli-info"><h2>Split (2017)</h2></span> </a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div id="topview-today" class="movies-list movies-list-full tab-pane in fade">
                            <div id="content-box"></div>
                            <!-- -->
                            <div class="clearfix"></div>
                        </div>
                        <div id="top-favorite" class="movies-list movies-list-full tab-pane in fade">
                            <div id="content-box"></div>
                            <!-- -->
                            <div class="clearfix"></div>
                        </div>
                        <div id="top-rating" class="movies-list movies-list-full tab-pane in fade">
                            <div id="content-box"></div>
                            <!-- -->
                            <div class="clearfix"></div>
                        </div>
                        <div id="top-imdb" class="movies-list movies-list-full tab-pane in fade">
                            <div id="content-box"></div>
                            <!-- -->
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
                <!--latest movies-->
                <div class="movies-list-wrap mlw-latestmovie">
                    <div class="ml-title"> <span class="pull-left">Latest Movies <i class="fa fa-chevron-right ml10"></i></span> <a href="https://123moviestoday.com/movie/filter/movie" class="pull-right cat-more">View more »</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="movies-list movies-list-full tab-pane in fade active">

                        @foreach($movies as $movie) 
                        @php
                            $uploads = $movie->Uploads()->get();
                            if(!count($uploads)) continue;
                        @endphp
                        <div data-movie-id="{{ $movie->id }}" class="ml-item">
                            <a href="/watch/{{$movie->id}}/{{str_slug($movie->title, '-')}}" data-url="/imdb/info?id={{$movie->id}}" class="ml-mask jt" title="{{$movie->title }} ({{$movie->year}})"> <span class="mli-quality">DVD</span> <img data-original="/cdn/posters/{{$movie->id}}.jpg" class="lazy thumb mli-thumb" alt="{{$movie->title }} ({{$movie->year}})"> <span class="mli-info"><h2>{{$movie->title }} ({{$movie->year}})</h2></span> </a>
                        </div>
                        @endforeach

                        <div class="clearfix"></div>
                    </div>
                </div>
                <!--/latest movies-->
                <!--latest tv-series-->
                <div class="movies-list-wrap mlw-featured">
                    <div class="ml-title"> <span class="pull-left">Latest TV-Series <i class="fa fa-chevron-right ml10"></i></span> <a href="https://123moviestoday.com/movie/filter/series" class="pull-right cat-more">View more »</a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="movies-list movies-list-full">
                        <div data-movie-id="23815" class="ml-item">
                            <a href="https://123moviestoday.com/film/reign-season-4-2017/" data-url="https://123moviestoday.com/ajax/movie_load_info/23815" class="ml-mask jt" title="Reign - Season 4 (2017)"> <span class="mli-eps">Eps<i>6</i></span> <img data-original="https://img.123movies.film/crop/215/310/media/images/170210_094837/reign.jpg" class="lazy thumb mli-thumb" alt="Reign - Season 4 (2017)"> <span class="mli-info"><h2>Reign - Season 4 (2017)</h2></span> </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <!--/latest tv-series-->
                <!--requested-->
                <div class="movies-list-wrap mlw-requested">
                    <div class="ml-title"> <span class="pull-left">Requested <i class="fa fa-chevron-right ml10"></i></span> <a href="https://123moviestoday.com/movie/filter/all" class="pull-right cat-more">View more »</a> </div>
                    <div class="clearfix"></div>
                    <div class="movies-list movies-list-full">
                        <div data-movie-id="24466" class="ml-item">
                            <a href="https://123moviestoday.com/film/may-2002/" data-url="https://123moviestoday.com/ajax/movie_load_info/24466" class="ml-mask jt" title="May (2002)"> <span class="mli-quality">DVD</span> <img data-original="https://img.123movies.film/crop/215/310/media/images/170324_071454/may.jpg" class="lazy thumb mli-thumb" alt="May (2002)"> <span class="mli-info"><h2>May (2002)</h2></span> </a>
                        </div>

                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
            <!--/requested-->
            <style type="text/css" media="screen">
                h1.pull-right {
                    font-size: 16px;
                    margin: 3px 0 0 0;
                    padding: 0;
                }
            </style>
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
        </div>
    </div>
@endsection
