<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'tag',
    ];

    public function galleries()
    {
        return $this->hasMany(ProductGallery::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }
}
