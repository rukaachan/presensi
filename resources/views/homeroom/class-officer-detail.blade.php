@extends('layout.layout')
@section('title', __('pages.class_officer_detail'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $officer->student?->name }}</h3><p>{{ __('positions.'.$officer->position) }} · {{ $officer->student?->classroom?->name }}</p><a href="{{ route('homeroom.class-officers.index') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
