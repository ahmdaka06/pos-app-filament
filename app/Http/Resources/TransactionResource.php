<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'customer_id' => $this->customer_id,
            'payment_method_id' => $this->payment_method_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'sku' => $i->sku,
                'price' => $i->price,
                'quantity' => $i->quantity,
                'discount' => $i->discount,
                'line_total' => $i->line_total,
            ])),
            'receipt' => $this->when($request->routeIs('*.show'), fn () => $this->toReceiptArray()),
        ];
    }
}
