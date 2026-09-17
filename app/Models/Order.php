<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function histories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
