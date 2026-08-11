@php
    $attendanceRecord = $record ?? null;
    $recordId = data_get($attendanceRecord, 'id');
    $evidencePath = data_get($attendanceRecord, 'evidence_path');
@endphp
@if ($recordId && $evidencePath)
    <a href="{{ route('attendance.evidence', ['attendanceRecord' => $recordId]) }}" target="_blank" rel="noopener" class="evidence-link"><img src="{{ route('attendance.evidence', ['attendanceRecord' => $recordId]) }}" class="evidence-preview" alt="{{ $alt ?? __('accessibility.attendance_evidence') }}"></a>
@else
    <span class="text-muted">{{ __('attendance.no_evidence') }}</span>
@endif
