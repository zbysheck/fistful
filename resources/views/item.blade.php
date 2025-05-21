<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $medium->title }} – Reference Wiki</title>
    <link rel="stylesheet"
          href="https://en.wikipedia.org/w/load.php?modules=skins.vector.styles&only=styles&skin=vector">
    <style>
        body {
            background-color: #f6f6f6;
            font-family: sans-serif;
            line-height: 1.6;
            padding: 1em;
        }

        #content {
            max-width: 960px;
            margin: auto;
            background-color: white;
            padding: 2em;
            border: 1px solid #ccc;
        }

        .firstHeading {
            font-size: 1.8em;
            border-bottom: 1px solid #aaa;
            margin-bottom: 0.5em;
        }

        h2 {
            border-bottom: 1px solid #ccc;
            margin-top: 2em;
        }

        .mw-tag {
            background: #e0e0e0;
            padding: 0.2em 0.5em;
            border-radius: 3px;
            margin-right: 0.3em;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div id="content" class="mw-body" role="main">
    <div class="mw-parser-output">
        <h1 class="firstHeading">{{ $medium->title }} {{ $medium->year ? '(' . $medium->year . ')' : '' }}</h1>
        <p>
            <b>Type:</b> {{ $medium->type->name }}<br>
            {{ $medium->description }}
            @if ($medium->year)
                <b>Year:</b> {{ $medium->year }}
            @endif
        </p>

        @if($medium->inspiredBy->count())
            <h2><span class="mw-headline" id="InspiredBy">Inspired By</span></h2>
            <ul>
                @foreach($medium->inspiredBy as $ref)
                    <li>
                        <a href="{{ url('/item/' . $ref->inspiredBy->slug) }}">{{ $ref->inspiredBy->title }}</a>
                        ({{ $ref->inspiredBy->type->name }}) {{$ref->description}}
                    </li>
                @endforeach
            </ul>
        @endif

        @if($medium->inspired->count())
            <h2><span class="mw-headline" id="Inspired">Inspired Works</span></h2>
            <ul>
                @foreach($medium->inspired as $ref)
                    <li>
                        <a href="{{ url('/item/' . $ref->inspired->slug) }}">{{ $ref->inspired->title }}</a>
                        ({{ $ref->inspired->type->name }}) {{$ref->description}}
                    </li>
                @endforeach
            </ul>
        @endif

        @if (!empty($medium->tags))
            <h2><span class="mw-headline" id="Tags">Tags</span></h2>
            <p>
                @foreach(explode(',', $medium->tags) as $tag)
                    <span class="mw-tag">{{ trim($tag) }}</span>
                @endforeach
            </p>
        @endif
    </div>
</div>
</body>
</html>
