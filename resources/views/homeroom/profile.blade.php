@extends('layout.layout')
@section('title', __('pages.profile'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $teacher->name }}</h3><p>{{ $teacher->account?->username }}</p><p>{{ __('roles.homeroom_teacher') }}</p><a href="{{ route('homeroom.dashboard') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
