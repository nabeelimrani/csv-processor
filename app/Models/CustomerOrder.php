<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $table = 'customer_orders';
    protected $primaryKey = 'id';

    protected $fillable = ['customer_email', 'order_date', 'product_quantity'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->order_date = $model->order_date ?: now(); // Set a default value if not provided
        });
        static::creating(function ($model) {
            $model->product_quantity = $model->product_quantity ?: 0; // Set a default value if not provided
        });
    }}
