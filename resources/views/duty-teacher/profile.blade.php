@extends('layout.layout')
@section('title', __('pages.profile'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $teacher?->name }}</h3><p>{{ $teacher?->account?->username }}</p><a href="{{ route('duty-teacher.dashboard') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
