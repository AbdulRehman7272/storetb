<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canDo('manage-products');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'in:variant'],
            'color' => ['nullable', 'string', 'max:100'],
            'swatch' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'size' => ['nullable', 'string', 'max:100', 'not_regex:/,/'],
            'regular_price' => ['required_if:product_type,single', 'nullable', 'numeric', 'min:0'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'sku' => ['nullable', 'string', 'max:255'],
            'vendor_reference' => ['nullable', 'string', 'max:255'],
            'vendor_code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['exists:collections,id'],
            'tags_text' => ['nullable', 'string'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.color' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.swatch' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants.*.size' => ['nullable', 'string', 'max:100', 'not_regex:/,/'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:255', 'distinct'],
            'variants.*.vendor_reference' => ['nullable', 'string', 'max:255'],
            'variants.*.vendor_code' => ['nullable', 'string', 'max:255'],
            'variants.*.regular_price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
            'variants.*.images' => ['nullable', 'array', 'max:10'],
            'variants.*.images.*' => ['image', 'max:4096'],
            'variants.*.remove_images' => ['nullable', 'array'],
            'variants.*.remove_images.*' => ['string', 'max:500'],
            'variants.*.enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $product = $this->route('product');
            $productId = $product instanceof Product ? $product->id : null;
            $mainCode = trim((string) $this->input('vendor_code'));
            $submittedCodes = [];

            if ($mainCode !== '') {
                $usedByProduct = Product::query()->where('vendor_code', $mainCode)
                    ->when($productId, fn ($query) => $query->whereKeyNot($productId))->exists();
                $usedByVariant = ProductVariant::query()->where('vendor_code', $mainCode)->exists();
                if ($usedByProduct || $usedByVariant) {
                    $validator->errors()->add('vendor_code', 'This vendor code is already assigned to another product or variant.');
                }
                $submittedCodes[strtolower($mainCode)] = 'vendor_code';
            }

            foreach ($this->input('variants', []) as $index => $variant) {
                if (! filled($variant['regular_price'] ?? null) && ! filled($variant['sale_price'] ?? null)) {
                    $validator->errors()->add("variants.$index.sale_price", 'Enter a full price or sale price.');
                }
                $colour = strtolower(trim((string) ($variant['color'] ?? '')));
                $hasColourImage = collect($this->input('variants', []))->contains(function ($candidate, $candidateIndex) use ($colour) {
                    if (strtolower(trim((string) ($candidate['color'] ?? ''))) !== $colour) return false;
                    $existingImage = filled($candidate['id'] ?? null)
                        && ProductVariant::query()->whereKey($candidate['id'])->whereNotNull('image')->exists();
                    return $existingImage || $this->hasFile("variants.$candidateIndex.image") || $this->hasFile("variants.$candidateIndex.images");
                });
                $firstColour = strtolower(trim((string) data_get($this->input('variants', []), '0.color', '')));
                $legacyImage = $colour === $firstColour && $product instanceof Product
                    && $product->variants()->doesntExist() && $product->primaryMedia()->exists();
                if (! $hasColourImage && ! $legacyImage) {
                    $validator->errors()->add("variants.$index.image", 'Add at least one image for this colour.');
                }
                $code = trim((string) ($variant['vendor_code'] ?? ''));
                if ($code === '') continue;

                $key = strtolower($code);
                $variantId = filled($variant['id'] ?? null) ? (int) $variant['id'] : null;
                $usedByProduct = Product::query()->where('vendor_code', $code)
                    ->when($productId, fn ($query) => $query->whereKeyNot($productId))->exists();
                $usedByVariant = ProductVariant::query()->where('vendor_code', $code)
                    ->when($variantId, fn ($query) => $query->whereKeyNot($variantId))->exists();

                if (isset($submittedCodes[$key]) || $usedByProduct || $usedByVariant) {
                    $validator->errors()->add("variants.$index.vendor_code", 'This vendor code is already assigned to another product or variant.');
                }
                $submittedCodes[$key] = "variants.$index.vendor_code";
            }
        });
    }
}
