<?php

namespace App\Models;

use DateTime;
use GuzzleHttp\Psr7\Query;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node\Expr\FuncCall;

class Book extends Model
{
    use HasFactory; 

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function scopeTitle(Builder $query, string $title) : Builder {
        return $query->where('title', 'LIKE', "%$title%");
    }

    public function scopePopular(Builder $query, DateTime $from = null, DateTime $to = null) : Builder | QueryBuilder {
        return $query->withCount([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ])->orderBy('reviews_count', 'desc');
    }

    public function scopeHighestRated(Builder $query, DateTime $from = null, DateTime $to = null) : Builder | QueryBuilder {
        return $query->withAvg([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating')->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, int $minReviews) : Builder | QueryBuilder {
        return $query->having('reviews_count', '>=', $minReviews);
    }

    private function dateRangeFilter(Builder $query, DateTime $from = null, DateTime $to = null) {
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } else if (!$from && $to) {
            $query->where('created_at', '<=', $to);
        } else if ($from && $to) {
            if ($from > $to) {
                return; // Or handle the error as appropriate
            }
            $query->whereBetween('created_at', [$from, $to]);
        }
    }
}
