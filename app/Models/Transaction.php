<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'user_id', 'customer_id', 'payment_method_id',
        'status', 'subtotal', 'discount_total', 'tax_total', 'grand_total',
        'paid_amount', 'change_amount', 'idempotency_key', 'note',
        'voided_at', 'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function scopeMine(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function toReceiptArray(): array
    {
        $store = StoreSetting::current();

        return [
            'store' => [
                'name' => $store->store_name,
                'address' => $store->address,
                'footer' => $store->receipt_footer,
            ],
            'invoice_number' => $this->invoice_number,
            'date' => $this->created_at?->toIso8601String(),
            'items' => $this->items->map(fn (TransactionItem $i) => [
                'name' => $i->product_name,
                'sku' => $i->sku,
                'price' => $i->price,
                'quantity' => $i->quantity,
                'discount' => $i->discount,
                'line_total' => $i->line_total,
            ])->all(),
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'status' => $this->status->value,
        ];
    }
}
