@php
    $targetPath = data_get($record ?? null, 'evidence_path');
    $targetId = data_get($record ?? null, 'attendance_record_id');
    $legacyFilename = data_get($record ?? null, 'foto_bukti');
    $legacyPath = $legacyFilename ? public_path('presensi_bukti/'.$legacyFilename) : null;
@endphp

@if ($targetPath && $targetId)
    <img src="{{ route('attendance.evidence', $targetId) }}" class="evidence-preview" alt="{{ $alt ?? 'Bukti kehadiran' }}">
@elseif ($legacyFilename && $legacyPath && file_exists($legacyPath))
    <img src="{{ asset('presensi_bukti/'.$legacyFilename) }}" class="evidence-preview" alt="{{ $alt ?? 'Bukti kehadiran' }}">
@else
    <span class="evidence-placeholder"><i class="ph-bold ph-image-square" aria-hidden="true"></i> Bukti tidak tersedia</span>
@endif
