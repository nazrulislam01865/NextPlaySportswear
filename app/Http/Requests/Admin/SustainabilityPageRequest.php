<?php

namespace App\Http\Requests\Admin;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class SustainabilityPageRequest extends FormRequest
{
    public const FEATURE_IDS = ['materials_waste', 'people_partners', 'packaging_delivery'];
    public const INFO_CARD_IDS = ['materials', 'partners', 'packaging'];

    public function authorize(): bool { return true; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $rules = [
            'hero' => ['required','array:eyebrow,title,subtitle'],
            'hero.eyebrow' => ['required','string','max:120'], 'hero.title' => ['required','string','max:255'], 'hero.subtitle' => ['required','string','max:1000'],
            'approach' => ['required','array:title,description'], 'approach.title' => ['required','string','max:255'], 'approach.description' => ['required','string','max:2000'],
            'features' => ['required','array','size:3'], 'features.*' => ['required','array:id,heading,description,image_alt'], 'features.*.id' => ['required','string'], 'features.*.heading' => ['required','string','max:255'], 'features.*.description' => ['required','string','max:1500'], 'features.*.image_alt' => ['required','string','max:255'],
            'informed' => ['required','array:title,subtitle,cards'], 'informed.title' => ['required','string','max:255'], 'informed.subtitle' => ['required','string','max:1000'], 'informed.cards' => ['required','array','size:3'], 'informed.cards.*' => ['required','array:id,title,description,icon_alt'], 'informed.cards.*.id' => ['required','string'], 'informed.cards.*.title' => ['required','string','max:255'], 'informed.cards.*.description' => ['required','string','max:1000'], 'informed.cards.*.icon_alt' => ['required','string','max:255'],
            'cta' => ['required','array:title,description,primary_label,primary_url,secondary_label,secondary_url'], 'cta.title' => ['required','string','max:255'], 'cta.description' => ['required','string','max:1000'], 'cta.primary_label' => ['required','string','max:100'], 'cta.primary_url' => $this->destinationRules(), 'cta.secondary_label' => ['required','string','max:100'], 'cta.secondary_url' => $this->destinationRules(),
            'seo' => ['required','array:title,description'], 'seo.title' => ['required','string','max:255'], 'seo.description' => ['required','string','max:500'],
        ];
        for ($i=0; $i<3; $i++) {
            $rules['feature_image_'.$i] = $this->imageRules(); $rules['remove_feature_image_'.$i] = ['nullable','boolean'];
            $rules['info_card_icon_'.$i] = $this->imageRules(); $rules['remove_info_card_icon_'.$i] = ['nullable','boolean'];
        }
        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateIds($validator, 'features', self::FEATURE_IDS);
            $this->validateIds($validator, 'informed.cards', self::INFO_CARD_IDS);
        });
    }

    /** @return array<string,mixed> */
    public function validatedContent(): array { return Arr::only($this->validated(), ['hero','approach','features','informed','cta','seo']); }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['hero','approach','features','informed','cta','seo'] as $key) if ($this->has($key) && is_array($this->input($key))) $merge[$key] = $this->trimValues($this->input($key));
        for ($i=0; $i<3; $i++) { $merge['remove_feature_image_'.$i] = $this->boolean('remove_feature_image_'.$i); $merge['remove_info_card_icon_'.$i] = $this->boolean('remove_info_card_icon_'.$i); }
        $this->merge($merge);
    }

    private function imageRules(): array { return ['nullable','file','image','mimes:jpg,jpeg,png,webp,avif','mimetypes:image/jpeg,image/png,image/webp,image/avif','max:2048']; }
    private function destinationRules(): array { return ['required','string','max:2048', function (string $attribute, mixed $value, Closure $fail): void { if (! is_string($value) || ! PublicUrl::isAllowed($value) || trim($value) === '#') $fail('The '.$attribute.' must be a safe relative site path or valid HTTP/HTTPS URL.'); }]; }
    /** @param array<int,string> $expected */
    private function validateIds(Validator $validator, string $key, array $expected): void { $items = $this->input($key, []); if (! is_array($items) || count($items)!==count($expected)) return; $ids=array_map(static fn($item)=>is_array($item)&&isset($item['id'])?(string)$item['id']:null,$items); if ($ids!==$expected) $validator->errors()->add($key,'The fixed Sustainability slots cannot be added, removed, duplicated, or reordered.'); }
    private function trimValues(mixed $value, ?string $key=null): mixed { if (is_array($value)) { foreach ($value as $k=>$v) $value[$k]=$this->trimValues($v,(string)$k); return $value; } return is_string($value)&&$key!=='id'?trim($value):$value; }
}
