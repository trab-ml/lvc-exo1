<?php
namespace App\Views;

class CashierView {
    private array $stock_list;
    private array $amount_list;
    private string $transactionMsg;

    public function __construct(array $stock_list, array $amount_list) {
        $this->stock_list = $stock_list;
        $this->amount_list = $amount_list;
        $this->transactionMsg = "";
    }

    public function display_stock(): void {
        if (!isset($this->stock_list)) {
            echo "<p>Stock vide</p>";
            return;
        }

        echo "<ul>";
        foreach ($this->stock_list as $currency) {
            $val = (string) $currency['value'];
            $img_link = $this->amount_list[$val]['img'];
            echo "<li class='stock-list'>
                    <img src='$img_link' alt='give me that, it s only paper:)'/>
                    <span class='xxl col-red'> X {$currency['qty']}</span>
                </li>";
        }
        echo "</ul>";
    }

    public function get_transaction_msg(): string {
        return $this->transactionMsg;
    }

    public function set_transaction_msg(string $msg): void {
        $this->transactionMsg = $msg;
    }
}
