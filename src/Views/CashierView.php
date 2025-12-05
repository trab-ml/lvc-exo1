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

        echo "<section class='w-full bg-white pb-[1rem] pt-[1rem] m-[0.5rem] dark:bg-dark stock-ctn border border-solid rounded-lg'>
            <div class='mx-auto px-2 sm:container'>
                <div class='mb-2'>
                    <h2 class='mb-2 text-2xl font-semibold text-dark sm:text-[28px] dark:text-white'>
                        Dans la caisse
                    </h2>
                    <p class='text-base text-body-color dark:text-dark-6'>
                        Le stock disponible en base de données.
                    </p>
                </div>
                <div class='-mx-2 flex flex-wrap'>";

        foreach ($this->stock_list as $currency) {
            $val = (string) $currency['value'];
            $img_link = $this->amount_list[$val]['img'];

            echo "<div class='w-full px-2 sm:w-1/2 lg:w-1/4 xl:w-1/4'>
                    <div class='mb-4'>
                        <div class='mb-[4px] overflow-hidden rounded-sm'>
                            <img src='$img_link' alt='give me that, it s only paper:)'
                                class='h-full w-full object-cover object-center' />
                            <span class='xxl col-red'> X {$currency['qty']}</span>
                        </div>
                    </div>
                </div>";
        }
        echo "</div> </div> </section>";
    }

    public function get_transaction_msg(): string {
        return $this->transactionMsg;
    }

    public function set_transaction_msg(string $msg): void {
        $this->transactionMsg = $msg;
    }
}
