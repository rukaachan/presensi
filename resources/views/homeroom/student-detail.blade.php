@extends('layout.layout')
@section('title', __('pages.student_detail'))
@section('content')<section class="workspace-page"><div class="card p-4"><h3>{{ $student->name }}</h3><p>{{ $student->student_number }} · {{ $student->classroom?->grade_level }} {{ $student->classroom?->name }}</p><p>{{ __('gender.'.$student->gender) }} · {{ __('status.'.$student->status) }}</p><a href="{{ route('homeroom.students.index') }}" class="btn btn-secondary">{{ __('actions.back') }}</a></div></section>@endsection
