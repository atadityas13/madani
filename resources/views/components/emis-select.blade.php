@props([
    'name',
    'options' => [],
    'value' => '',
    'required' => false,
    'placeholder' => 'Pilih',
    'disabledValues' => [],
])
@php
    $disabledValues = array_map('strval', $disabledValues);
@endphp
<select {{ $attributes->merge(['class' => 'form-select']) }} name="{{ $name }}" @required($required)>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $val => $label)
        <option value="{{ $val }}" @selected((string) $value === (string) $val) @disabled(in_array((string) $val, $disabledValues, true))>{{ $label }}</option>
    @endforeach
</select>
