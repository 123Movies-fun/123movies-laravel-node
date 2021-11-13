<ul style="margin-bottom: 0;">

	@foreach($movies as $movie)
	    <li>
	        <a style="background-image: url(/cdn/posters/{{$movie->id}}.jpg)" class="thumb" href="/watch/{{$movie->id}}/{{str_slug($movie->title, '-')}}"></a>
	        <div class="ss-info"> 
	        	<a href="/watch/{{$movie->id}}/{{str_slug($movie->title, '-')}}" class="ss-title">{{$movie->title}} ({{$movie->year}})</a>
	            <p>{{$movie->runtime}}</p> 

	            @php $i = 0; @endphp
	          	@foreach($movie->Genres($limit = 3) as $genre)
	          		@php 
	          			$i++;
	          		 	if($i > 3) break; 
	          		@endphp
	            	<a href="/genre/{{strtolower($genre->genre)}}" title="{{$genre->genre}}">{{$genre->genre}}</a>@if($i != 3),@endif
	            @endforeach

	        </div>
	        <div class="clearfix"></div> 
	    </li>
	 @endforeach

    <li class="ss-bottom" style="padding: 0; border-bottom: none;"> <a href="/movie/search/{{str_replace(' ', '_', $keyword)}}" id="suggest-all">View all</a>
    </li>
</ul>