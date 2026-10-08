<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\ProductFormRequest;
use App\Models\ProductionMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ProductionMethodOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_different_methods_can_use_the_same_quantity_range(): void
    {
        $errors = $this->validateProductionRules([
            'standard' => [$this->rule(1, 100, 7, 10)],
            'rush' => [$this->rule(1, 100, 3, 5, 2.50)],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_overlapping_ranges_within_one_method_are_still_rejected(): void
    {
        $errors = $this->validateProductionRules([
            'standard' => [
                $this->rule(1, 100, 7, 10),
                $this->rule(50, null, 10, 12),
            ],
            'rush' => [$this->rule(1, 100, 3, 5)],
        ]);

        $this->assertArrayHasKey('production_method_rules.standard.1.minimum_quantity', $errors);
        $this->assertArrayNotHasKey('production_method_rules.rush.0.minimum_quantity', $errors);
    }

    public function test_default_method_is_saved_first_for_automatic_quantity_matching(): void
    {
        $rush = ProductionMethod::query()->create([
            'name' => 'Rush', 'code' => 'rush', 'is_active' => true,
            'is_default' => false, 'sort_order' => 10,
        ]);
        $standard = ProductionMethod::query()->create([
            'name' => 'Standard', 'code' => 'standard', 'is_active' => true,
            'is_default' => true, 'sort_order' => 30,
        ]);

        $request = ProductFormRequest::create('/admin/products', 'POST', [
            'production_method_codes' => ['rush', 'standard'],
            'production_method_rules' => [
                'rush' => [$this->rule(1, 100, 3, 5, 3.00)],
                'standard' => [$this->rule(1, 100, 7, 10)],
            ],
        ]);

        $speeds = (new ReflectionMethod(ProductFormRequest::class, 'productionMethodsFromMaster'))
            ->invoke($request);

        $this->assertSame([$standard->id, $rush->id], array_column($speeds, 'production_method_id'));
        $this->assertSame([7, 3], array_column($speeds, 'minimum_days'));
        $this->assertEqualsWithDelta(3.00, $speeds[1]['price_adjustment'], 0.001);
    }

    private function validateProductionRules(array $rules): array
    {
        $data = [
            'production_methods_enabled' => '1',
            'production_method_codes' => array_keys($rules),
            'production_method_rules' => $rules,
        ];
        $request = ProductFormRequest::create('/admin/products', 'POST', $data);
        $validator = Validator::make($data, []);
        $request->withValidator($validator);
        $validator->fails();

        return $validator->errors()->toArray();
    }

    private function rule(int $min, ?int $max, int $minDays, int $maxDays, float $charge = 0): array
    {
        return [
            'minimum_quantity' => $min,
            'maximum_quantity' => $max,
            'minimum_days' => $minDays,
            'maximum_days' => $maxDays,
            'price_adjustment' => $charge,
        ];
    }
}
