@props([
    'name',
    'options' => [],
    'value' => '',
    'required' => false,
    'placeholder' => 'Pilih',
])
<select {{ $attributes->merge(['class' => 'form-select']) }} name="{{ $name }}" @required($required)>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $val => $label)
        <option value="{{ $val }}" @selected((string) $value === (string) $val)>{{ $label }}</option>
    @endforeach
</select>
