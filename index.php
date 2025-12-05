<?php
error_reporting(E_ALL);

require_once __DIR__ . "/src/Config/bootstrap.php";

use App\Config\DatabaseConnection;
use App\Models\CashierModel;
use App\Views\CashierView;

global $amounts;

try {
    $cashier_model = new CashierModel($amounts);
    // $cashier_model->initialize_stock();

    $stock_list = $cashier_model->fetch_stock_order_by_desc();
    $cashier_view = new CashierView($stock_list, $amounts);

    if (isset($_POST['submit'])) {
        $cashier_view->set_transaction_msg(
            $cashier_model->handle_payment()
        );
    }
} catch (Exception $ex) {
    echo $ex->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <title>Cashier</title>
    <link rel="stylesheet" type="text/css" href="public/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
    <h1 class="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Bienvenue sur votre distributeur de cash inversé, LE CASHIER.</h1>
    <main>
        <?php $cashier_view->display_stock(); ?>

            <div class="computation-ctn m-[0.5rem]">
            <section>
                <form action="index.php" method="post" class="flex flex-col justify-center align-center">
                    <h2 class="text-xl/5 font-bold text-gray-700 sm:truncate sm:text-2xl sm:tracking-tight">Passer à la caisse</h2>
                    <div class="mb-2">
                        <label for="loan">Vous devez (€) </label>
                        <input
                            value="<?php echo $_POST["loan"] ?? 0; ?>"
                            step="0.01"
                            type="number"
                            id="loan"
                            name="loan"
                            placeholder="Loan"
                            class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-white" 
                        />
                    </div>

                    <div class="mb-2">
                        <label for="loan">À régler (€) </label>
                        <input
                            value="<?php echo $_POST["debt"] ?? 0; ?>"
                            step="0.01"
                            type="number"
                            id="debt"
                            name="debt"
                            placeholder="Debt"
                            class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-white" 
                        />
                    </div>

                    <button
                        name="submit"
                        type="submit"
                        value="Payer"
                        class="inline-block mx-auto rounded-lg border border-white px-8 py-3 text-center text-base font-semibold text-white transition hover:bg-white hover:text-primary">
                        Payer
                    </button>
                </form>
            </section>

            <?php echo $cashier_view->get_transaction_msg(); ?>
        </div>
    </main>
</body>
</html>

<?php
DatabaseConnection::close_connexion();
?>
