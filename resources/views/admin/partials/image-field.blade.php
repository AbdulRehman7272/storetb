@php
    $isVariantGallery = str_starts_with($name, 'variants[');
    $gallery = $isVariantGallery && isset($variant['id'])
        ? collect(\App\Models\ProductVariant::find($variant['id'])?->images ?: [])->prepend($current)->filter()->unique()
        : collect([$current])->filter();
@endphp

@if($isVariantGallery)
    <div class="admin-image-field admin-variant-gallery {{ ($wide ?? false) ? 'wide' : '' }}" data-admin-image-picker data-admin-gallery-picker>
        <span class="admin-image-field__label">Variant images</span>
        <div class="variant-gallery-preview" data-gallery-preview>
            @foreach($gallery as $galleryImage)
                <span class="variant-gallery-item" data-existing-image="{{ $galleryImage }}">
                    <img src="{{ asset($galleryImage) }}" alt="Variant image preview">
                    <button type="button" class="variant-gallery-remove" data-remove-gallery-image aria-label="Remove image">&times;</button>
                </span>
            @endforeach
            <span class="variant-gallery-empty" data-gallery-empty @if($gallery->isNotEmpty()) hidden @endif>No images selected</span>
        </div>
        <label class="action-btn secondary variant-gallery-choose">
            <i data-lucide="images"></i>Choose multiple images
            <input type="file" name="{{ str_replace('[image]', '[images][]', $name) }}" accept="image/*" multiple data-admin-gallery-input data-admin-image-input>
        </label>
        <small>Select up to 10 photos together. Scroll sideways when the gallery is full.</small>
        <span data-removed-images></span>
    </div>
@else
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
@endif
