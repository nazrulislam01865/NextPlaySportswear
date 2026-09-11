<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BulkQuoteRequest extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_QUOTATION_PREPARING = 'quotation_preparing';
    public const STATUS_QUOTATION_SENT = 'quotation_sent';
    public const STATUS_CUSTOMER_RESPONDED = 'customer_responded';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const FLOWTRACK_SYNC_PENDING = 'pending';
    public const FLOWTRACK_SYNC_SYNCING = 'syncing';
    public const FLOWTRACK_SYNC_SYNCED = 'synced';
    public const FLOWTRACK_SYNC_FAILED = 'failed';
    public const FLOWTRACK_SYNC_DISABLED = 'disabled';

    protected $fillable = [
        'reference',
        'user_id',
        'full_name',
        'organization',
        'email',
        'phone',
        'customer_account',
        'product_type',
        'estimated_quantity',
        'sizes_needed',
        'budget_range',
        'artwork_details',
        'customization_types',
        'shipping_address',
        'country',
        'state_province',
        'postal_code',
        'preferred_shipping_method',
        'needed_by',
        'event_date',
        'attachment',
        'additional_notes',
        'status',
        'assigned_to',
        'priority',
        'quoted_amount',
        'quote_currency',
        'admin_note',
        'last_contacted_at',
        'closed_at',
        'closed_by',
        'flowtrack_sync_status',
        'flowtrack_sync_attempts',
        'flowtrack_inquiry_id',
        'flowtrack_inquiry_number',
        'flowtrack_last_attempt_at',
        'flowtrack_synced_at',
        'flowtrack_sync_error',
        'flowtrack_response',
        'ip_hash',
        'user_agent_hash',
    ];

    protected $hidden = [
        'ip_hash',
        'user_agent_hash',
    ];

    /** @return array<string,string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_QUOTATION_PREPARING => 'Quotation Preparing',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_CUSTOMER_RESPONDED => 'Customer Responded',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /** @return array<string,string> */
    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BulkQuoteActivity::class)->latest('occurred_at');
    }

    public function estimatedQuantityLabel(): string
    {
        return match ($this->estimated_quantity) {
            '10-49' => '10–49 pieces',
            '50-99' => '50–99 pieces',
            '100-499' => '100–499 pieces',
            '500-999' => '500–999 pieces',
            '1000-plus' => '1,000+ pieces',
            default => $this->humanizeValue($this->estimated_quantity),
        };
    }

    public function budgetRangeLabel(): string
    {
        return match ($this->budget_range) {
            'under-500' => 'Under $500',
            '500-1500' => '$500–$1,500',
            '1500-5000' => '$1,500–$5,000',
            '5000-plus' => '$5,000+',
            'not-sure' => 'Not sure yet',
            null, '' => '—',
            default => $this->humanizeValue($this->budget_range),
        };
    }

    public function preferredShippingMethodLabel(): string
    {
        return match ($this->preferred_shipping_method) {
            'standard' => 'Standard',
            'express' => 'Express',
            'rush' => 'Rush',
            'recommend' => 'Recommend the best option',
            null, '' => '—',
            default => $this->humanizeValue($this->preferred_shipping_method),
        };
    }

    /** @return array<int, string> */
    public function customizationLabels(): array
    {
        return collect((array) $this->customization_types)
            ->map(fn ($value): string => match ((string) $value) {
                'logo' => 'Logo',
                'names-numbers' => 'Names & Numbers',
                'full-custom-design' => 'Full Custom Design',
                'embroidery' => 'Embroidery',
                'sublimation' => 'Sublimation',
                'not-sure' => 'Not Sure',
                default => $this->humanizeValue((string) $value),
            })
            ->filter()
            ->values()
            ->all();
    }

    private function humanizeValue(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '—' : Str::of($value)->replace(['_', '-'], ' ')->title()->toString();
    }

    protected function casts(): array
    {
        return [
            'customer_account' => 'array',
            'customization_types' => 'array',
            'attachment' => 'array',
            'needed_by' => 'date',
            'event_date' => 'date',
            'quoted_amount' => 'decimal:2',
            'last_contacted_at' => 'datetime',
            'closed_at' => 'datetime',
            'flowtrack_sync_attempts' => 'integer',
            'flowtrack_last_attempt_at' => 'datetime',
            'flowtrack_synced_at' => 'datetime',
            'flowtrack_response' => 'array',
        ];
    }
}
