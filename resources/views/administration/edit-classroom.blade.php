@extends('layout.layout')
@section('title', __('pages.edit_classroom'))
@section('content')<section class="workspace-page"><form method="POST" action="{{ route('administration.classrooms.update',['id'=>$classroom->id]) }}" class="card p-4">@csrf @method('PUT')@include('administration.partials.classroom-form',['classroom'=>$classroom])<button class="btn btn-primary mt-4">{{ __('actions.save') }}</button><a href="{{ route('administration.classrooms.index') }}" class="btn btn-secondary mt-4">{{ __('actions.cancel') }}</a></form></section>@endsection
