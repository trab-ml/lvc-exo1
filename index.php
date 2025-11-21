<?php
error_reporting(E_ALL);

include "database.php";
include "utils.php";
global $conn, $amounts;

function handle_error(mysqli_sql_exception $ex) {
    echo"<div class='col-red'><span>Error while inserting : </span><p class='xl'>{$ex}</p></div>";
}

function close_connexion() {
    global $conn;
    mysqli_close($conn);
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

            mysqli_query($conn, $insert);
        }
    } catch (mysqli_sql_exception $ex) {
        handle_error($ex);
    }
}

function display_stock() {
    global $conn, $amounts;

    try {
        $select = "SELECT qty, value FROM stock WHERE qty > 0";
        $result = mysqli_query($conn, $select);

        if (mysqli_num_rows($result) > 0) {
            echo"<ul>";
            while ($row = mysqli_fetch_assoc($result)) {
                $val = $row['value'];
//                echo"qty: {$row['qty']}; value: {$row['value']}; type: {$row['type']}; <br>";
                echo "<li class='stock-list'>
                    <img src='{$amounts[$val]['img']}' alt='give me that, it s only paper:)'/>
                    <span class='xxl'> X {$row['qty']}</span>
                </li>";
            }
            echo"</ul>";
        } else {
            echo"Stock vide";
        }
    } catch (mysqli_sql_exception $ex) {
        handle_error($ex);
    }
}

function fetch_stock(): array {
    global $conn;
    $select = "SELECT qty, value, type FROM stock WHERE qty > 0 ORDER BY value DESC";
    $query = mysqli_query($conn, $select);
    return mysqli_fetch_all($query, MYSQLI_ASSOC);
}

function format_due_amount_line_msg($msg): string {
    return "<section><p class='col-red'>{$msg}</p></section>";
}

function format_due_amount_line($type, $val, $qty): string {
    return "<p class='col-red'>{$type}(s) de {$val} <span class='xxl'>X {$qty}</span>.</p>";
}

function handle_payment(): string {
    $loan = filter_var($_POST['loan'], FILTER_SANITIZE_NUMBER_INT);
    $debt = filter_var($_POST['debt'], FILTER_SANITIZE_NUMBER_INT);

    if ($loan <= 0) {
        return format_due_amount_line_msg("Vous n'avez rien à payer !");
    } else if ($debt < $loan) {
        return format_due_amount_line_msg("Le montant à payer ne peut être inférieur à l'emprunt effectué !");
    }

//    echo"<p class='col-red'>1. ARRIVE THERE :) </p>";
    try {
        $stock_list_by_value_desc = fetch_stock();
        $stock_list_length = count($stock_list_by_value_desc);
    } catch (mysqli_sql_exception $ex) {
        echo"Unexpected error: " . $ex; // TODO: use handle_error($msg, $ex);
        return format_due_amount_line_msg("Change indisponible, veuillez réessayer ultérieurement s'il vous plaît !");
    }

//    echo"<p class='col-red'>2. ARRIVE THERE :) </p>";
    $result = "Stock indisponible";
    if ($stock_list_length > 0) {
//        echo"<p class='col-red'>3. ARRIVE THERE :) </p>";
        $amount_to_return = $debt - $loan;
        $result = "<section><h2>Je dois vous rendre : </h2>";

        for ($i = 0; $i < $stock_list_length; $i++) {
            if ($amount_to_return == 0)
                break;

//            echo"<p class='col-red'>4. ARRIVE THERE :) </p>";
            $val = $stock_list_by_value_desc[$i]['value'];
            $mod = $amount_to_return % $val;
            $available_qty = $stock_list_by_value_desc[$i]['qty'];
            $needed_qty = 1;
            if ($mod == 0) {
//                echo"<p class='col-red'>5. ARRIVE THERE :) </p>";
                $needed_qty = $amount_to_return / $val;
                if ($needed_qty <= $available_qty) {
//                    echo"<p class='col-red'>6. ARRIVE THERE :) </p>";
                    // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
//                    $amount_to_return = 0;
                    $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
                    $result .= "</section>";
                    return $result;
                } else {
                    // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                    $amount_to_return -= $available_qty * $val;
                    $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $available_qty);
                }
            } else if ($amount_to_return >= $val) {
//                echo"<p class='col-red'>. ARRIVE THERE :) </p>";
                // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                $amount_to_return -= $val;
                $result .= format_due_amount_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
            }
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
            <h2><label for="loan">Vous devez </label><input type="number" id="loan" name="loan" value="<?php echo $_POST["loan"]; ?>"/> €</h2>
            <p><label for="debt">À régler : </label><input type="number" id="debt" name="debt" value="<?php echo $_POST["debt"]; ?>"/> €</p>
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
