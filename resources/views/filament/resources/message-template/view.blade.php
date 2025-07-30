{{--
<div>
    <div>
        <strong>Kategori:</strong> {{ \Illuminate\Support\Str::of($record->category)->replace('-', ' ')->title() }}
    </div>
    <div>
        <strong>Judul:</strong> {{ $record->title }}
    </div>
    <div>
        <strong>Pesan:</strong>
        <div style="white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere;">
            {{ $record->message }}
        </div>
    </div>
    <div>
        <strong>Status:</strong> {{ $record->is_active ? 'Aktif' : 'Nonaktif' }}
    </div>
    <div>
        <strong>Dibuat:</strong> {{ $record->created_at?->diffForHumans() }}
    </div>
    <div>
        <strong>Update Terakhir:</strong> {{ $record->updated_at?->diffForHumans() }}
    </div>
</div>--}}
