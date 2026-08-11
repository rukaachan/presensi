@extends('layout.layout')
@section('title', __('pages.edit_student'))
@section('content')<section class="workspace-page"><form method="POST" action="{{ route('administration.students.update',['id'=>$student->id]) }}" enctype="multipart/form-data" class="card p-4">@csrf @method('PUT')@include('administration.partials.student-form',['student'=>$student])<button class="btn btn-primary mt-4">{{ __('actions.save') }}</button><a href="{{ route('administration.students.index') }}" class="btn btn-secondary mt-4">{{ __('actions.cancel') }}</a></form></section>@endsection
