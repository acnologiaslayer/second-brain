@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-zinc-700']) }}>
    {{ $value ?? $slot }}
</label>
