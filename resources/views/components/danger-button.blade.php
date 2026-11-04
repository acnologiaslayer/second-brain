<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-red-600 text-white hover:bg-red-700 shadow-sm']) }}>
    {{ $slot }}
</button>
