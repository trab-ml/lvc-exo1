<?php
error_reporting(E_ALL);

require_once "src/database.php";
require_once "src/utils.php";
require_once "src/models/CashierModel.php";
require_once "src/views/CashierView.php";
require_once "src/amounts.php";

global $conn, $amounts;
try {
    $cashier_model = new CashierModel($conn, $amounts);
    //$cashier_model->initialize_stock();

    $stock_list = $cashier_model->fetch_stock_order_by_desc();
    $cashier_view = new CashierView($stock_list, $amounts);

    if (isset($_POST['submit'])) {
        $cashier_view->set_transaction_msg(handle_payment());
    }

} catch (Exception $ex) {
    echo $ex->getMessage();
}

function compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return, $is_decimal = false): string {
    global $cashier_view;
    $result = "";

    for ($i = 0; $i < $stock_list_length && $amount_to_return != 0; $i++) {
        $val = $stock_list_by_value_desc[$i]['value'];
        $mod = $is_decimal ? ($amount_to_return * 100) % ($val * 100) : $amount_to_return % $val;
        $available_qty = $stock_list_by_value_desc[$i]['qty'];
        $needed_qty = 1;
        if ($mod == 0) {
            $needed_qty = $amount_to_return / $val;
            if ($needed_qty <= $available_qty) {
                // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                $amount_to_return = 0;
                $result .= $cashier_view->format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
                break;
            } else {
                // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                $amount_to_return -= $available_qty * $val;
                $result .= $cashier_view->format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $available_qty);
            }
        } else if ($amount_to_return >= $val) {
            // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
            $amount_to_return -= $val;
            $result .= $cashier_view->format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
        }
    }

    return $result;
}

function handle_payment(): string {
    global $cashier_model, $cashier_view;

    $loan = filter_var($_POST['loan'], FILTER_VALIDATE_FLOAT);
    $debt = filter_var($_POST['debt'], FILTER_VALIDATE_FLOAT);

    if ($loan <= 0) {
        return $cashier_view->format_due_amount_line_msg("Vous n'avez rien à payer !");
    } else if ($debt < $loan) {
        return $cashier_view->format_due_amount_line_msg("Le montant à payer ($debt €) ne peut être inférieur à l'emprunt ($loan €) effectué !");
    }

    try {
        $stock_list_by_value_desc = $cashier_model->fetch_stock_order_by_desc();
        $stock_list_length = count($stock_list_by_value_desc);
    } catch (PDOException $ex) {
        return $cashier_view->format_due_amount_line_msg("Change indisponible, veuillez réessayer ultérieurement s'il vous plaît !");
    }

    $result = "Stock indisponible";
    if ($stock_list_length > 0) {
        $due_amount = $debt - $loan;
        $amount_to_return = (int) floor($due_amount);
        $decimal_part_of_amount_to_return = $due_amount - $amount_to_return;

        $result = "<section><h2>Je dois vous rendre : </h2>";
        $result .= "<p class='col-orange m'>(Montant à payer: $debt €, Emprunt: $loan €)</p>";
        $result .= "<p class='col-orange m'>(À payer: Partie entière: $amount_to_return, Partie décimale: $decimal_part_of_amount_to_return)</p>";

        $result .= compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return);
        if ($decimal_part_of_amount_to_return > 0) {
            $result .= compute_due_amount($stock_list_by_value_desc, $stock_list_length, $decimal_part_of_amount_to_return, true);
        }
    }

    $result .= "</section>";
    return $result;
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
        <?php global $cashier_view; $cashier_view->display_stock() ?>
    </section>
    <section>
        <form action="index.php" method="post">
            <h2><label for="loan">Vous devez </label><input type="number" id="loan" name="loan" value="<?php echo $_POST["loan"]; ?>" step="0.01"/> €</h2>
            <p><label for="debt">À régler : </label><input type="number" id="debt" name="debt" value="<?php echo $_POST["debt"]; ?>" step="0.01"/> €</p>
            <button name="submit" type="submit" value="Payer">Payer</button>
        </form>
    </section>

    <?php global $cashier_view; echo handle_payment(); ?>
</main>
</body>
</html>


<?php
global $cashier_model;
$cashier_model->close_connexion();
?>
