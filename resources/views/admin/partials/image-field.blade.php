<label class="admin-image-field {{ ($wide ?? false) ? 'wide' : '' }}" data-admin-image-picker>
    <span class="admin-image-field__label">{{ $label }}</span>
    <span class="admin-image-field__content">
        <span class="admin-image-field__preview">
            <img src="{{ $current ? asset($current) : '' }}" alt="{{ $label }} preview" data-admin-image-preview @if(!$current) hidden @endif>
            <span data-admin-image-empty @if($current) hidden @endif>No image selected</span>
            <em data-admin-image-state>{{ $current ? 'Active image' : 'New image' }}</em>
        </span>
        <span class="action-btn secondary"><i data-lucide="upload"></i>Choose image</span>
        <input type="file" name="{{ $name }}" accept="image/*" data-admin-image-input>
    </span>
</label>
