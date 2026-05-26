<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'customer_id',
        'label',
        'line_1', 
        'line_2', 
        'city', 
        'province', 
        'postal_code', 
        'country',
        'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
