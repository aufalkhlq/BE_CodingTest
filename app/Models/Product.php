<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
        ];
    }

    /**
     * Cari berdasarkan nama atau kategori.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $query, string $term): void {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $like)
                ->orWhere('category', 'like', $like));
        });
    }

    public function scopeWithStatus(Builder $query, ProductStatus|string|null $status): void
    {
        $query->when($status, fn (Builder $query, $status) => $query->where('status', $status));
    }

    /**
     * Jumlah produk keseluruhan dan per status.
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        $counts = static::query()
            ->toBase() 
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $perStatus = [];

        foreach (ProductStatus::cases() as $status) {
            $perStatus[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return ['total' => array_sum($perStatus)] + $perStatus;
    }
}
