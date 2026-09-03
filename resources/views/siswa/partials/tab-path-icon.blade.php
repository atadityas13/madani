@if ($state === 'current')
    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
    <span class="visually-hidden">Sedang diisi</span>
@elseif ($state === 'done')
    <i class="bi bi-check-lg" aria-hidden="true"></i>
    <span class="visually-hidden">Sudah lengkap</span>
@elseif ($state === 'todo')
    <i class="bi bi-x-lg" aria-hidden="true"></i>
    <span class="visually-hidden">Belum lengkap</span>
@else
    <span class="visually-hidden">Opsional</span>
@endif
