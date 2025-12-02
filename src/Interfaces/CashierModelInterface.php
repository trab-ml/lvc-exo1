<?php
namespace App\Interfaces;

interface CashierModelInterface {
    public function insert_amount(array $amount): void;
    public function update_amount(int $id, int $newQty): void;
    public function fetch_stock_order_by_desc(): array;
    public function handle_payment(): string;
}
