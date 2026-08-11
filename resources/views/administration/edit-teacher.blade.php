@extends('layout.layout')
@section('title', __('pages.edit_teacher'))
@section('content')<section class="workspace-page"><form method="POST" action="{{ route('administration.teachers.update',['id'=>$teacher->id]) }}" enctype="multipart/form-data" class="card p-4">@csrf @method('PUT')@include('administration.partials.teacher-form',['teacher'=>$teacher])<button class="btn btn-primary mt-4">{{ __('actions.save') }}</button><a href="{{ route('administration.teachers.index') }}" class="btn btn-secondary mt-4">{{ __('actions.cancel') }}</a></form></section>@endsection
