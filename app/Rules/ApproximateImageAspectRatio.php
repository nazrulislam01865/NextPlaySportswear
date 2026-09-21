<?php

namespace App\Rules;

use App\Support\HomepageImageAspectRatios;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ApproximateImageAspectRatio implements ValidationRule
{
    /** @param array{ratio:string,width:int,height:int,label:string,tolerance?:float} $definition */
    public function __construct(private readonly array $definition)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $path = $value->getRealPath();
        if (! is_string($path) || $path === '') {
            return;
        }

        $dimensions = @getimagesize($path);
        if (! is_array($dimensions) || ! isset($dimensions[0], $dimensions[1])) {
            return;
        }

        if (HomepageImageAspectRatios::matches((int) $dimensions[0], (int) $dimensions[1], $this->definition)) {
            return;
        }

        $ratio = (string) $this->definition['ratio'];
        $label = (string) ($this->definition['label'] ?? 'uploaded');
        $percent = (int) round(((float) ($this->definition['tolerance'] ?? HomepageImageAspectRatios::DEFAULT_TOLERANCE)) * 100);

        $fail(sprintf(
            'The %s image should use an approximately %s aspect ratio (±%d%% accepted). Any suitable resolution is accepted.',
            $label,
            $ratio,
            $percent,
        ));
    }
}
