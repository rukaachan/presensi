@extends('layout.layout')
@section('title', __('pages.profile'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $student->name }}</h3><p>{{ $student->student_number }} · {{ $student->classroom?->grade_level }} {{ $student->classroom?->name }}</p><p>{{ $student->account?->username }}</p><a href="{{ route('student.dashboard') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
