<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table): void {
                $table->id();
                $table->string('question', 500);
                $table->text('answer');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('faq_product')) {
            Schema::create('faq_product', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('faq_id')->constrained('faqs')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'faq_id']);
                $table->index(['product_id', 'sort_order']);
            });
        }

        if (Schema::hasTable('product_faqs')) {
            $masterFaqIds = [];

            DB::table('product_faqs')
                ->orderBy('id')
                ->get()
                ->each(function (object $legacyFaq) use (&$masterFaqIds): void {
                    $question = trim((string) $legacyFaq->question);
                    $answer = trim((string) $legacyFaq->answer);

                    if ($question === '' || $answer === '') {
                        return;
                    }

                    $key = hash('sha256', mb_strtolower($question).'|'.$answer);
                    $faqId = $masterFaqIds[$key] ?? null;

                    if (! $faqId) {
                        $faqId = DB::table('faqs')->insertGetId([
                            'question' => $question,
                            'answer' => $answer,
                            'is_active' => (bool) $legacyFaq->is_active,
                            'sort_order' => (int) $legacyFaq->sort_order,
                            'created_at' => $legacyFaq->created_at ?? now(),
                            'updated_at' => $legacyFaq->updated_at ?? now(),
                        ]);
                        $masterFaqIds[$key] = $faqId;
                    } elseif ((bool) $legacyFaq->is_active) {
                        DB::table('faqs')->where('id', $faqId)->update([
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('faq_product')->insertOrIgnore([
                        'faq_id' => $faqId,
                        'product_id' => (int) $legacyFaq->product_id,
                        'sort_order' => (int) $legacyFaq->sort_order,
                        'created_at' => $legacyFaq->created_at ?? now(),
                        'updated_at' => $legacyFaq->updated_at ?? now(),
                    ]);
                });

            Schema::drop('product_faqs');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_faqs')) {
            Schema::create('product_faqs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('question', 500);
                $table->text('answer');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['product_id', 'is_active', 'sort_order']);
            });
        }

        if (Schema::hasTable('faqs') && Schema::hasTable('faq_product')) {
            DB::table('faq_product')
                ->join('faqs', 'faqs.id', '=', 'faq_product.faq_id')
                ->select([
                    'faq_product.product_id',
                    'faqs.question',
                    'faqs.answer',
                    'faqs.is_active',
                    'faq_product.sort_order',
                    'faq_product.created_at',
                    'faq_product.updated_at',
                ])
                ->orderBy('faq_product.id')
                ->get()
                ->each(function (object $faq): void {
                    DB::table('product_faqs')->insert([
                        'product_id' => $faq->product_id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'is_active' => $faq->is_active,
                        'sort_order' => $faq->sort_order,
                        'created_at' => $faq->created_at ?? now(),
                        'updated_at' => $faq->updated_at ?? now(),
                    ]);
                });
        }

        Schema::dropIfExists('faq_product');
        Schema::dropIfExists('faqs');
    }
};
