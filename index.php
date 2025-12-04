<?php
error_reporting(E_ALL);

require_once  __DIR__ . "/src/Config/bootstrap.php";

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
</head>
<body>
    <h1>Bienvenue sur votre distributeur de cash inversé, LE CASHIER.</h1>
    <main>
        <section>
            <h2>Dans la caisse :</h2>
            <?php global $cashier_view;
            $cashier_view->display_stock() ?>
        </section>
        <section>
            <form action="index.php" method="post">
                <h2><label for="loan">Vous devez </label><input type="number" id="loan" name="loan"
                        value="<?php echo $_POST["loan"] ?? 0; ?>" step="0.01" /> €</h2>
                <p><label for="debt">À régler : </label><input type="number" id="debt" name="debt"
                        value="<?php echo $_POST["debt"] ?? 0; ?>" step="0.01" /> €</p>
                <button name="submit" type="submit" value="Payer">Payer</button>
            </form>
        </section>

        <?php global $cashier_view;
        echo $cashier_view->get_transaction_msg(); ?>
    </main>
</body>
</html>

<?php
DatabaseConnection::close_connexion();
?>
