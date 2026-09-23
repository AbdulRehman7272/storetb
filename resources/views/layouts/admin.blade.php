@php
    use App\Support\StoreSettings;
    $storeName = StoreSettings::get('store_name', 'TBrand');
    $logo = StoreSettings::get('main_logo', 'brand/tbrand/01_Main_Horizontal_Black.png');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - {{ $storeName }}</title>
    <link rel="icon" href="{{ asset(StoreSettings::get('favicon', 'brand/tbrand/favicon.ico')) }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/conca.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/select2/select2.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}"><img src="{{ asset($logo) }}" alt="{{ $storeName }}"></a>
        <nav>
            @foreach([
                ['Dashboard','admin.dashboard','layout-dashboard'],
                ['Products','admin.products.index','package'],
                ['Categories','admin.categories.index','folder-tree'],
                ['Collections','admin.collections.index','layers-3'],
                ['Orders','admin.orders.index','shopping-bag'],
                ['Customers','admin.customers.index','users'],
                ['Payments','admin.payment-accounts.index','landmark'],
                ['Inventory','admin.inventory.index','warehouse'],
                ['Reports','admin.reports.index','chart-column'],
                ['Pages','admin.pages.index','files'],
                ['Staff & Roles','admin.roles.index','shield-check'],
                ['Settings','admin.settings.edit','settings'],
            ] as [$label,$route,$icon])
                <a class="{{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}"><i data-lucide="{{ $icon }}"></i><span>{{ $label }}</span></a>
            @endforeach
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-top">
            <div><p class="breadcrumb-lite">Admin / @yield('title', 'Dashboard')</p><h1>@yield('title', 'Dashboard')</h1></div>
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="icon-btn icon-btn--danger" title="Logout" aria-label="Logout"><i data-lucide="log-out"></i></button></form>
        </header>
        @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content admin-confirm-modal">
            <div class="modal-header">
                <div class="admin-confirm-modal__heading">
                    <span class="admin-confirm-modal__icon"><i data-lucide="triangle-alert"></i></span>
                    <h2 class="modal-title" id="deleteConfirmTitle">Confirm deletion</h2>
                </div>
                <button type="button" class="icon-btn admin-confirm-modal__close" data-bs-dismiss="modal" aria-label="Close" title="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p>Delete <strong data-delete-name></strong> permanently?</p>
                <small data-delete-note>This item can only be deleted when it is not in use.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" data-delete-form>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn admin-danger-btn"><i data-lucide="trash-2"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('admin-assets/js/bootstrap.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{$('.admin-multiselect').select2({width:'100%',closeOnSelect:false,placeholder:'Select items'});});</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('deleteConfirmModal');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    const form = element.querySelector('[data-delete-form]');
    const name = element.querySelector('[data-delete-name]');
    const note = element.querySelector('[data-delete-note]');

    document.querySelectorAll('[data-delete-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            form.action = trigger.dataset.deleteAction;
            name.textContent = trigger.dataset.deleteName;
            const notes = {
                category: 'Categories containing products or child categories cannot be deleted.',
                collection: 'Products assigned to this collection will remain in your store.',
                product: 'Products used in an order or customer cart cannot be deleted.',
            };
            note.textContent = notes[trigger.dataset.deleteKind] || 'This item will be deleted permanently.';
            modal.show();
        });
    });
});
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-admin-image-input]');
    if (!input || !input.files?.[0]) return;
    const picker = input.closest('[data-admin-image-picker]');
    if (input.multiple) {
        const selectedFiles = [...(input._galleryFiles || []), ...input.files].filter((file, index, files) =>
            files.findIndex((item) => item.name === file.name && item.size === file.size && item.lastModified === file.lastModified) === index
        ).slice(0, 10);
        input._galleryFiles = selectedFiles;
        const selectedTransfer = new DataTransfer();
        selectedFiles.forEach((file) => selectedTransfer.items.add(file));
        input.files = selectedTransfer.files;
        const gallery = picker.querySelector('[data-gallery-preview]');
        gallery.querySelectorAll('[data-new-preview]').forEach((item) => item.remove());
        selectedFiles.forEach((file, fileIndex) => {
            const item = document.createElement('span');
            item.className = 'variant-gallery-item';
            item.dataset.newPreview = 'true';
            item.dataset.fileIndex = fileIndex;
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'variant-gallery-remove';
            remove.dataset.removeGalleryImage = 'true';
            remove.setAttribute('aria-label', 'Remove image');
            remove.innerHTML = '&times;';
            item.append(image, remove);
            gallery.append(item);
        });
        const empty = gallery.querySelector('[data-gallery-empty]');
        if (empty) empty.hidden = true;
        picker.classList.add('has-selection');
        return;
    }
    const preview = picker.querySelector('[data-admin-image-preview]');
    const empty = picker.querySelector('[data-admin-image-empty]');
    const state = picker.querySelector('[data-admin-image-state]');
    const reader = new FileReader();
    reader.onload = () => {
        preview.src = reader.result;
        preview.hidden = false;
        empty.hidden = true;
        state.textContent = 'Selected replacement';
        picker.classList.add('has-selection');
    };
    reader.readAsDataURL(input.files[0]);
});
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-gallery-image]');
    if (!button) return;
    event.preventDefault();
    const item = button.closest('.variant-gallery-item');
    const picker = button.closest('[data-admin-gallery-picker], .admin-variant-gallery');
    const input = picker?.querySelector('input[type="file"][multiple]');
    const existingPath = item?.dataset.existingImage;
    if (existingPath && input) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = input.name.replace('[images][]', '[remove_images][]');
        hidden.value = existingPath;
        picker.querySelector('[data-removed-images]')?.append(hidden);
    } else if (item?.dataset.fileIndex && input) {
        const removedIndex = Number(item.dataset.fileIndex);
        input._galleryFiles = (input._galleryFiles || [...input.files]).filter((file, index) => index !== removedIndex);
        const transfer = new DataTransfer();
        input._galleryFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        return;
    }
    item?.remove();
    const gallery = picker?.querySelector('[data-gallery-preview]');
    const empty = gallery?.querySelector('[data-gallery-empty]');
    if (empty && !gallery.querySelector('.variant-gallery-item')) empty.hidden = false;
});
const upgradeVariantGallery = (root = document) => {
    root.querySelectorAll?.('.variant-card input[type="file"][name$="[image]"]').forEach((input) => {
        input.name = input.name.replace(/\[image\]$/, '[images][]');
        input.multiple = true;
        input.dataset.adminGalleryInput = 'true';
        const picker = input.closest('[data-admin-image-picker]');
        picker?.classList.add('admin-variant-gallery');
        const legacyContent = picker?.querySelector('.admin-image-field__content');
        if (legacyContent) {
            const legacyPreview = legacyContent.querySelector('.admin-image-field__preview');
            const choose = document.createElement('label');
            choose.className = 'action-btn secondary variant-gallery-choose';
            choose.innerHTML = '<i data-lucide="images"></i>Choose multiple images';
            choose.append(input);
            const help = document.createElement('small');
            help.textContent = 'Select up to 10 photos together. Scroll sideways when the gallery is full.';
            const removed = document.createElement('span');
            removed.dataset.removedImages = '';
            const empty = document.createElement('span');
            empty.className = 'variant-gallery-empty';
            empty.dataset.galleryEmpty = '';
            empty.textContent = 'No images selected';
            legacyPreview?.append(empty);
            picker.append(legacyPreview, choose, help, removed);
            legacyContent.remove();
        }
        const preview = picker?.querySelector('.admin-image-field__preview, .variant-gallery-preview');
        if (preview) {
            preview.classList.add('variant-gallery-preview');
            preview.dataset.galleryPreview = 'true';
        }
        const label = picker?.querySelector('.admin-image-field__label');
        const action = picker?.querySelector('.action-btn');
        if (label) label.textContent = 'Variant images';
        if (action) action.lastChild.textContent = 'Choose multiple images';
    });
};
upgradeVariantGallery();
new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach((node) => {
    if (node.nodeType === 1) upgradeVariantGallery(node);
}))).observe(document.body, { childList: true, subtree: true });
document.querySelectorAll('textarea[name="description"]').forEach((textarea) => {
    if (textarea.dataset.richEditorReady) return;
    textarea.dataset.richEditorReady = 'true';
    const wrappingLabel = textarea.parentElement?.tagName === 'LABEL' ? textarea.parentElement : null;
    const editor = document.createElement('div');
    editor.className = 'admin-rich-editor';
    editor.innerHTML = `
        <div class="admin-rich-editor__toolbar" role="toolbar" aria-label="Description formatting">
            <select data-rich-block aria-label="Text style"><option value="p">Paragraph</option><option value="h2">Heading</option><option value="h3">Subheading</option><option value="blockquote">Quote</option></select>
            <button type="button" data-rich-command="bold" title="Bold"><strong>B</strong></button>
            <button type="button" data-rich-command="italic" title="Italic"><em>I</em></button>
            <button type="button" data-rich-command="underline" title="Underline"><u>U</u></button>
            <button type="button" data-rich-command="insertUnorderedList" title="Bullet list">&#8226; List</button>
            <button type="button" data-rich-command="insertOrderedList" title="Numbered list">1. List</button>
            <button type="button" data-rich-link title="Insert link">Link</button>
            <button type="button" data-rich-command="removeFormat" title="Clear formatting">Clear</button>
            <button type="button" data-rich-command="undo" title="Undo">&#8630;</button>
            <button type="button" data-rich-command="redo" title="Redo">&#8631;</button>
        </div>
        <div class="admin-rich-editor__surface" contenteditable="true" role="textbox" aria-multiline="true"></div>`;
    if (wrappingLabel) {
        const field = document.createElement('div');
        field.className = `${wrappingLabel.className} form-field`.trim();
        const caption = document.createElement('div');
        caption.className = 'form-field__label';
        caption.textContent = 'Description';
        wrappingLabel.before(field);
        field.append(caption, editor, textarea);
        wrappingLabel.remove();
    } else {
        textarea.before(editor);
    }
    textarea.hidden = true;
    const surface = editor.querySelector('.admin-rich-editor__surface');
    surface.innerHTML = textarea.value.trim() || '<p><br></p>';
    const sync = () => { textarea.value = surface.innerHTML; };
    editor.addEventListener('click', (event) => event.stopPropagation());
    surface.addEventListener('focus', () => {
        if (!surface.textContent.trim() && !surface.querySelector('br')) {
            surface.innerHTML = '<p><br></p>';
        }
    });
    editor.querySelectorAll('[data-rich-command]').forEach((button) => button.addEventListener('click', () => {
        surface.focus();
        document.execCommand(button.dataset.richCommand, false);
        sync();
    }));
    editor.querySelector('[data-rich-block]').addEventListener('change', (event) => {
        surface.focus();
        document.execCommand('formatBlock', false, event.target.value);
        sync();
    });
    editor.querySelector('[data-rich-link]').addEventListener('click', () => {
        const url = window.prompt('Enter link URL');
        if (!url) return;
        surface.focus();
        document.execCommand('createLink', false, url);
        sync();
    });
    surface.addEventListener('input', sync);
    textarea.form?.addEventListener('submit', sync);
});
</script>
@stack('scripts')
</body>
</html>
