<?php
error_reporting(E_ALL);

include "database.php";
include "utils.php";
global $conn, $amounts;
const INSERT_MSG_ERROR = "Error while inserting :";
const UNEXPECTED_MSG_ERROR = "Unexpected error: ";

function handle_error(string $msg, PDOException $ex) {
    echo"<div class='col-red'><span>{$msg} </span><p class='xl'>{$ex}</p></div>";
}

function close_connexion() {
    global $conn;
    $conn = null;
}

function initialize_stock() {
    global $amounts, $conn;
    $MIN = 1;
    $MAX = 10;

    try {
        foreach ($amounts as $amount) {
            $value = (float) $amount['value']; // refuse to take in count the float value as id, increment each time from last id
            $qty = rand($MIN, $MAX);
            $type = $amount['type'];

            $insert = "INSERT INTO stock (value, qty, type) 
                            VALUES ('$value', '$qty', '$type')";
            $conn->exec($insert);
        }
    } catch (PDOException $ex) {
        handle_error(INSERT_MSG_ERROR, $ex);
    }
}

function display_stock() {
    global $conn, $amounts;

    try {
        $select = "SELECT qty, value FROM stock WHERE qty > 0";
        $currencies = $conn->query($select)->fetchAll(PDO::FETCH_ASSOC);

        if (!isset($currencies)) {
            echo"Stock vide";
            return;
        }

        echo "<ul>";
        foreach ($currencies as $currency) {
            $val = $currency['value'];
            $img_link = $amounts[$val]['img'];
            echo "<li class='stock-list'>
                    <img src='$img_link' alt='give me that, it s only paper:)'/>
                    <span class='xxl'> X {$currency['qty']}</span>
                </li>";
        }
        echo "</ul>";
    } catch (PDOException $ex) {
        handle_error(INSERT_MSG_ERROR, $ex);
    }
}

function fetch_stock(): array {
    global $conn;
    $select = "SELECT qty, value, type FROM stock WHERE qty > 0 ORDER BY value DESC";
    return $conn->query($select)->fetchAll(PDO::FETCH_ASSOC);
}

function format_due_amount_line_msg($msg): string {
    return "<section class='col-red xxl'>{$msg}</section>";
}

function format_due_amount_line($type, $val, $qty): string {
    return "<p class='col-red'>{$type}(s) de {$val} <span class='xxl'>X {$qty}</span>.</p>";
}

function compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return, $is_decimal = false): string {
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
                $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
                break;
            } else {
                // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                $amount_to_return -= $available_qty * $val;
                $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $available_qty);
            }
        } else if ($amount_to_return >= $val) {
            // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
            $amount_to_return -= $val;
            $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
        }
    }

    return $result;
}

function handle_payment(): string {
    $loan = filter_var($_POST['loan'], FILTER_VALIDATE_FLOAT);
    $debt = filter_var($_POST['debt'], FILTER_VALIDATE_FLOAT);

    if ($loan <= 0) {
        return format_due_amount_line_msg("Vous n'avez rien à payer !");
    } else if ($debt < $loan) {
        return format_due_amount_line_msg("Le montant à payer ($debt €) ne peut être inférieur à l'emprunt ($loan €) effectué !");
    }

    try {
        $stock_list_by_value_desc = fetch_stock();
        $stock_list_length = count($stock_list_by_value_desc);
    } catch (PDOException $ex) {
        handle_error(UNEXPECTED_MSG_ERROR, $ex);
        return format_due_amount_line_msg("Change indisponible, veuillez réessayer ultérieurement s'il vous plaît !");
    }

    $result = "Stock indisponible";
    if ($stock_list_length > 0) {
        $due_amount = $debt - $loan;
        $amount_to_return = (int) floor($due_amount);
        $decimal_part_of_amount_to_return = $due_amount - $amount_to_return;

        $result = "<section><h2>Je dois vous rendre : </h2>";
        $result .= "<p class='col-orange'>(Montant à payer: $debt €, Emprunt: $loan €)</p>";
        $result .= "<p class='col-orange'>(À payer: Partie entière: $amount_to_return, Partie décimale: $decimal_part_of_amount_to_return)</p>";

        $result .= compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return);
        if ($decimal_part_of_amount_to_return > 0) {
            $result .= compute_due_amount($stock_list_by_value_desc, $stock_list_length, $decimal_part_of_amount_to_return, true);
        }
    }

    $result .= "</section>";
    return $result;
}

//if ($conn) {
//    initialize_stock();
//}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Cashier</title>
    <link rel="stylesheet" type="text/css" href="./css/styles.css">
</head>
<body>
<h1>Bienvenue sur votre distributeur de cash inversé, LE CASHIER.</h1>
<main>
    <section>
        <h2>Dans la caisse :</h2>
        <?php display_stock() ?>
    </section>
    <section>
        <form action="index.php" method="post">
            <h2><label for="loan">Vous devez </label><input type="number" id="loan" name="loan" value="<?php echo $_POST["loan"]; ?>" step="0.01"/> €</h2>
            <p><label for="debt">À régler : </label><input type="number" id="debt" name="debt" value="<?php echo $_POST["debt"]; ?>" step="0.01"/> €</p>
            <button name="submit" type="submit" value="Payer">Payer</button>
        </form>
    </section>

    <?php
    if (isset($_POST['submit'])) {
       echo handle_payment();
    }
    ?>
</main>
</body>
</html>

<?php
// Cleaning...
close_connexion();
?>
