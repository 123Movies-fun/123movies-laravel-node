<div class="jtip-quality">CAM</div>
<div class="jtip-top">
    <div class="jt-info jt-imdb">IMDb: 7.7</div>
    <div class="jt-info">{{$movie->year}}</div>
    <div class="jt-info">{{$movie->duration}}</div>
    <div class="clearfix"></div>
</div>
<p class="f-desc">{{$movie->plot}}</p>

    <div class="block">Country:
        <a href="https://gomovies.to/country/us" title="United States">{{$movie->country}}</a>    </div>
    <div class="block">Genre:
        <a href="https://gomovies.to/genre/musical/" title="Musical">Musical</a>, <a href="https://gomovies.to/genre/family/" title="Family">Family</a>, <a href="https://gomovies.to/genre/fantasy/" title="Fantasy">Fantasy</a>    </div>
<div class="jtip-bottom">
    <a href=""
       class="btn btn-block btn-successful"><i
            class="fa fa-play-circle mr10"></i>Watch movie</a>
    <button onclick="favorite(19879,1)"
            class="btn btn-block btn-default mt10 btn-favorite-19879 remove-favorite">
        <i class="fa fa-heart mr10"></i>Favorite
    </button>
</div>
