@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => '',
])

<div class="form-group">
    @if($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <select 
        id="{{ $name }}" 
        name="{{ $name }}" 
        class="form-control" 
        @if($required) required @endif
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $value => $text)
            <option value="{{ $value }}" @if($selected == $value) selected @endif>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @error($name)
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>