@extends('layout.layout')
@section('title', __('pages.duty_dashboard'))
@section('content')
<section class="dashboard-intro" aria-labelledby="dashboard-title"><div><p class="eyebrow">{{ __('pages.attendance_operations') }}</p><h2 id="dashboard-title">{{ __('pages.duty_headline') }}</h2><p>{{ __('pages.duty_description') }}</p></div><a class="quick-action rounded-md bg-accent text-accent-foreground ring-1 ring-accent" href="{{ route('duty-teacher.attendance.index') }}"><span>{{ __('nav.review_attendance') }}</span><i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a></section>
@php $metrics=[['label'=>__('attendance.confirmed'),'value'=>$totalPresent,'meta'=>__('attendance.recorded_count'),'tone'=>'metric-card--dark'],['label'=>__('attendance.excused'),'value'=>$totalExcused,'meta'=>__('attendance.approved_absence'),'tone'=>'metric-card--green'],['label'=>__('attendance.absent'),'value'=>$totalAbsent,'meta'=>__('attendance.needs_attention'),'tone'=>'']]; @endphp
<div class="metric-grid">@foreach($metrics as $metric)<article class="metric-card rounded-lg bg-card text-card-foreground ring-1 ring-border {{ $metric['tone'] }}"><span class="metric-label">{{ $metric['label'] }}</span><strong class="metric-value">{{ $metric['value'] }}</strong><p class="metric-meta">{{ $metric['meta'] }}</p></article>@endforeach</div>
@include('layout.partials.metric-bars',['metrics'=>$metrics,'visualTitle'=>__('pages.attendance_status'),'visualDescription'=>__('pages.attendance_status_description')])
@endsection
