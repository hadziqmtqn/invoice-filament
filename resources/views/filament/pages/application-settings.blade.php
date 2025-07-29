<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <x-filament::button type="submit" style="margin-top: 10px;">Submit</x-filament::button>
    </form>
</x-filament::page>