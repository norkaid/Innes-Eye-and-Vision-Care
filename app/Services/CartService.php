<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Shopping cart kept in the visitor's session: only product ids and quantities are
 * stored. Prices are always read fresh from the database.
 */
class CartService
{
    private const KEY = 'cart.items';

    /** @return array<int,int> product id => quantity */
    public function raw(): array
    {
        return array_filter((array) session(self::KEY, []), fn ($q) => (int) $q > 0);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function set(Product $product, int $quantity): int
    {
        $items = $this->raw();
        $quantity = max(0, min($quantity, $product->maxOrderQuantity()));
        if ($quantity === 0) {
            unset($items[$product->id]);
        } else {
            $items[$product->id] = $quantity;
        }
        session([self::KEY => $items]);

        return $quantity;
    }

    public function add(Product $product, int $quantity = 1): int
    {
        return $this->set($product, ($this->raw()[$product->id] ?? 0) + $quantity);
    }

    public function remove(int $productId): void
    {
        $items = $this->raw();
        unset($items[$productId]);
        session([self::KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Cart lines with current prices. Products that can no longer be bought are dropped
     * and quantities are reduced to what is in stock.
     *
     * @return Collection<int, array{product: Product, quantity: int, line_total: float}>
     */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        $products = Product::with('category')->whereIn('id', array_keys($raw))->get()->keyBy('id');
        $lines = collect();
        $changed = false;
        foreach ($raw as $id => $qty) {
            $product = $products->get($id);
            if (! $product || ! $product->canBeBought()) {
                $changed = true;

                continue;
            }
            $quantity = min($qty, $product->maxOrderQuantity());
            $changed = $changed || $quantity !== $qty;
            $lines->push(['product' => $product, 'quantity' => $quantity, 'line_total' => round((float) $product->price * $quantity, 2)]);
        }
        if ($changed) {
            session([self::KEY => $lines->mapWithKeys(fn ($l) => [$l['product']->id => $l['quantity']])->all()]);
        }

        return $lines;
    }

    public function subtotal(?Collection $lines = null): float
    {
        return round(($lines ?? $this->lines())->sum('line_total'), 2);
    }
}
