@extends('layouts.simple')

@section('content')

    <div class="container very-small mt-xl">
        <div class="card content-wrap auto-height">
            <h1 class="list-heading">{{ trans('auth.register_thanks') }}</h1>
            <p>{{ trans('auth.register_after') }}</p>
        </div>
    </div>

@stop