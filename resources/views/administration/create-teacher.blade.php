@extends('layout.layout')
@section('title', __('pages.create_teacher'))
@section('content')<section class="workspace-page"><form method="POST" action="{{ route('administration.teachers.store') }}" enctype="multipart/form-data" class="card p-4">@csrf@include('administration.partials.teacher-form',['teacher'=>null])<button class="btn btn-primary mt-4">{{ __('actions.save') }}</button><a href="{{ route('administration.teachers.index') }}" class="btn btn-secondary mt-4">{{ __('actions.cancel') }}</a></form></section>@endsection
