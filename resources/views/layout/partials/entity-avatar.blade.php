@php
    $avatarDirectory = trim($directory ?? '', '/');
    $avatarFilename = (string) ($filename ?? '');
    $avatarRelativePath = $avatarDirectory . '/' . $avatarFilename;
    $avatarExists = $avatarFilename !== '' && is_file(public_path($avatarRelativePath));
    $avatarVariant = $variant ?? 'table';
@endphp

@if ($avatarExists)
    <img src="{{ asset($avatarRelativePath) }}" alt="{{ $alt ?? '' }}" class="entity-avatar entity-avatar--{{ $avatarVariant }}">
@else
    <span class="entity-avatar entity-avatar--{{ $avatarVariant }} entity-avatar--fallback" role="img"
        aria-label="{{ $alt ?? 'Foto tidak tersedia' }}">
        <i class="ph-bold ph-user" aria-hidden="true"></i>
    </span>
@endif
