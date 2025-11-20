<?php
include "database.php";
include "utils.php";
global $amounts;
global $conn;

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
        echo"Error while inserting : <br>" . $ex;
    }
}

if ($conn) {
    initialize_stock();
    mysqli_close($conn);
}
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
        <ul>
            <?php
            echo "<li><img src='{$amounts['500']['img']}' alt='give me that, it s only paper:)'/><span class='x-times'> X 5</span></li>";
            ?>
        </ul>
    </section>
    <section>
        <form action="index.php" method="post">
            <h2><label for="loan">Vous devez </label><input type="number" id="loan" name="loan" value="0"/> €</h2>
            <p><label for="debt">À régler : </label><input type="number" id="debt" name="debt" value="0"/> €</p>
            <button name="submit" type="submit" value="Payer">Payer</button>
        </form>
    </section>
    <section>
        <h2>Je dois vous rendre :...</h2>
        <p class="col-red"> <?php echo"Vous avez emprunté {$_POST["loan"]} € et présenté {$_POST["debt"]} €" ?> </p>
    </section>
</main>
</body>
</html>
