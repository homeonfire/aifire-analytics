<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'school_id',
        'deal_id',
        'gc_payment_id',
        'client_name',
        'client_email',
        'gc_deal_number',
        'gc_created_at',
        'payment_system',
        'status',
        'amount',
        'commission_amount',
        'net_amount',
        'operation_id',
        'offer_name',
    ];

    protected $casts = [
        'gc_created_at' => 'datetime',
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }
}