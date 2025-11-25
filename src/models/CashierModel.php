<?php
const PATH_TO_INTERFACE_PACKAGE = __DIR__ . "/../interfaces";

require_once PATH_TO_INTERFACE_PACKAGE . "/FormatedDisplayInterface.php";
require_once PATH_TO_INTERFACE_PACKAGE . "/CashierModelInterface.php";
require_once PATH_TO_INTERFACE_PACKAGE . "/DatabaseInterface.php";

class CashierModel implements FormatedDisplayInterface, CashierModelInterface, DatabaseInterface {
    private ?PDO $db_conn;
    private Array $amount_list;

    const INSERT_MSG_ERROR = "Error while inserting : ";
    const SELECT_MSG_ERROR = "Error while fetching : ";
    const UPDATE_MSG_ERROR = "Error while updating : ";
    const MIN = 1, MAX = 10;

    public function __construct(PDO $db_conn, Array $amount_list) {
        $this->db_conn = $db_conn;
        $this->amount_list = $amount_list;
    }

    public function initialize_stock(): void {
        foreach ($this->amount_list as $amount) {
            $this->insert_amount($amount);
        }
    }

    public function insert_amount(Array $amount): void {
        try {
            $value = (float) $amount['value'];
            $qty = rand(self::MIN, self::MAX);
            $type = $amount['type'];

            $insert = "INSERT INTO stock (value, qty, type) 
                            VALUES (:value, :qty, :type)";
            $stmt = $this->db_conn->prepare($insert);
            $stmt->execute(['value' => $value, 'qty' => $qty, 'type' => $type]);
        } catch (PDOException $ex) {
            $this->handle_error(self::INSERT_MSG_ERROR, $ex);
        }
    }

    public function update_amount(int $id, int $newQty): void {
        try {
            $id = filter_var (id, FILTER_SANITIZE_NUMBER_INT);
            $newQty = filter_var (id, FILTER_SANITIZE_NUMBER_INT);

            $update = "UPDATE stock SET VALUES qty = :qty WHERE id = :id;";
            $stmt = $this->db_conn->prepare($update);
            $stmt->execute(['qty' => $newQty, 'id' => $id]);
        } catch (PDOException $ex) {
            $this->handle_error(self::UPDATE_MSG_ERROR, $ex);
        }
    }

    public function fetch_stock_order_by_desc(): array {
        $stock_records = [];
        try {
            $select = "SELECT id, qty, value, type FROM stock WHERE qty > 0 ORDER BY value DESC";
            $stock_records = $this->db_conn->query($select)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            $this->handle_error(self::SELECT_MSG_ERROR, $ex);
        }
        return $stock_records;
    }

    /**
     * To replace the redundant lines (3 times) : $amount_to_return = ...; $result .= ...;
     * @param int $id
     * @param int $newQty
     * @return string
     */
    private function update_stock_qty(int $id, int $newQty): string {
        /**
         * TODO: Not implemented yet
         */
        return "HEY, I'M VERY USEFUL :)"; // success info for result msg!
    }

    private function compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return, $is_decimal = false): string {
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
                    $result .= $this->format_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
                } else {
                    // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                    $amount_to_return -= $available_qty * $val;
                    $result .= $this->format_line($stock_list_by_value_desc[$i]['type'], $val, $available_qty);
                }
            } else if ($amount_to_return >= $val) {
                // TODO: mettre à jour le stock en bdd puis poursuivre en cas de succès, sinon avorter
                $amount_to_return -= $val;
                $result .= $this->format_line($stock_list_by_value_desc[$i]['type'], $val, $needed_qty);
            }
        }

        return $result;
    }

    public function handle_payment(): string {
        global $cashier_model, $cashier_view;

        $loan = filter_var($_POST['loan'], FILTER_VALIDATE_FLOAT);
        $debt = filter_var($_POST['debt'], FILTER_VALIDATE_FLOAT);

        if ($loan <= 0) {
            return $this->format_line_msg("Vous n'avez rien à payer !");
        }
        if ($debt < $loan) {
            return $this->format_line_msg("Le montant à payer ($debt €) ne peut être inférieur à l'emprunt ($loan €) effectué !");
        }

        try {
            $stock_list_by_value_desc = $cashier_model->fetch_stock_order_by_desc();
            $stock_list_length = count($stock_list_by_value_desc);
        } catch (PDOException $ex) {
            return $this->format_line_msg("Change indisponible, veuillez réessayer ultérieurement s'il vous plaît !");
        }

        $result = "Stock indisponible";
        if ($stock_list_length > 0) {
            $due_amount = $debt - $loan;
            $amount_to_return = (int) floor($due_amount);
            $decimal_part_of_amount_to_return = $due_amount - $amount_to_return;

            $result = "<section><h2>Je dois vous rendre : </h2>";
            $result .= "<p class='col-orange m'>(Montant à payer: $debt €, Emprunt: $loan €)</p>";
            $result .= "<p class='col-orange m'>(À payer: Partie entière: $amount_to_return, Partie décimale: $decimal_part_of_amount_to_return)</p>";

            $result .= $this->compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return);
            if ($decimal_part_of_amount_to_return > 0) {
                $result .= $this->compute_due_amount($stock_list_by_value_desc, $stock_list_length, $decimal_part_of_amount_to_return, true);
            }
        }

        $result .= "</section>";
        return $result;
    }

    public function format_line_msg($msg): string {
        return "<section class='col-red xxl'>{$msg}</section>";
    }

    public function format_line($type, $val, $qty): string {
        return "<p class='col-red'>{$type}(s) de {$val} <span class='xxl col-red'>X {$qty}</span>.</p>";
    }

    public function close_connexion(): void {
        $this->set_db_conn(null);
    }

    public function get_db_conn(): PDO {
        return $this->db_conn;
    }

    private function set_db_conn(?PDO $conn): void {
        $this->db_conn = $conn;
    }
}
