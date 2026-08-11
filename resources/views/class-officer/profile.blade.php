@extends('layout.layout')
@section('title', __('pages.profile'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $student->name }}</h3><p>{{ $student->student_number }} · {{ $student->classroom?->name }}</p><p>{{ __('positions.'.$student->position) }}</p><a href="{{ route('class-officer.dashboard') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
