@extends('layout.layout')
@section('title', __('pages.create_student'))
@section('content')<section class="workspace-page"><form method="POST" action="{{ route('administration.students.store') }}" enctype="multipart/form-data" class="card p-4">@csrf@include('administration.partials.student-form',['student'=>null])<button class="btn btn-primary mt-4">{{ __('actions.save') }}</button><a href="{{ route('administration.students.index') }}" class="btn btn-secondary mt-4">{{ __('actions.cancel') }}</a></form></section>@endsection
