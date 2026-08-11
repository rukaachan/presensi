@extends('group.layout')
@section('title', __('pages.profile_unavailable'))
@section('page-description', __('pages.profile_unavailable_description'))

@section('content')
    <section class="operations-empty-state profile-empty-state" aria-labelledby="profile-empty-title">
        <span class="profile-empty-mark"><i class="ph-bold ph-user-circle-dashed" aria-hidden="true"></i></span>
        <strong id="profile-empty-title">{{ __('pages.profile_unavailable_title') }}</strong>
        <p>{{ $message ?? __('common.profile_unavailable') }}</p>
        <a href="{{ $backUrl ?? url('/') }}" class="btn btn-primary"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i> {{ __('actions.back') }}</a>
    </section>
@endsection
