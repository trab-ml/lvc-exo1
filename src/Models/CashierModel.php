<?php
namespace App\Models;

use App\Config\DatabaseConnection;
use App\Abstracts\FormatedDisplayAbstract;
use App\Interfaces\CashierModelInterface;
use App\Exceptions\EdgeCaseException;
use \PDO, \PDOException;

class CashierModel extends FormatedDisplayAbstract implements CashierModelInterface {
    private ?PDO $db_conn;
    private array $amount_list;

    const INSERT_ERROR_MSG = "Error while inserting : ";
    const SELECT_ERROR_MSG = "Error while fetching : ";
    const UPDATE_ERROR_MSG = "Error while updating : ";
    const STOCK_UPDATE_ERROR_MSG = "Error while updating stock : ";
    const MIN = 1;
    const MAX = 10;

    public function __construct(array $amount_list) {
        $this->db_conn = DatabaseConnection::get_db_conn();
        $this->amount_list = $amount_list;
    }

    public function initialize_stock(): void {
        foreach ($this->amount_list as $amount) {
            $this->insert_amount($amount);
        }
    }

    public function insert_amount(array $amount): void {
        try {
            $value = (float) $amount['value'];
            $qty = rand(self::MIN, self::MAX);
            $type = $amount['type'];

            $insert = "INSERT INTO stock (value, qty, type)
                            VALUES (:value, :qty, :type)";
            $stmt = $this->db_conn->prepare($insert);
            $stmt->execute(['value' => $value, 'qty' => $qty, 'type' => $type]);
        } catch (PDOException $ex) {
            handle_error(self::INSERT_ERROR_MSG, $ex);
        }
    }

    public function update_amount(int $id, int $newQty): void {
        try {
            $id = filter_var ($id, FILTER_SANITIZE_NUMBER_INT);
            $newQty = filter_var ($newQty, FILTER_SANITIZE_NUMBER_INT);

            $update = "UPDATE stock SET qty = :newQty WHERE id = :id;";
            $stmt = $this->db_conn->prepare($update);
            $stmt->execute(['newQty' => $newQty, 'id' => $id]);
        } catch (PDOException $ex) {
            handle_error(self::UPDATE_ERROR_MSG, $ex);
        }
    }

    public function fetch_stock_order_by_desc(): array {
        $stock_records = [];
        try {
            $select = "SELECT id, qty, value, type FROM stock WHERE qty > 0 ORDER BY value DESC";
            $stock_records = $this->db_conn->query($select)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            handle_error(self::SELECT_ERROR_MSG, $ex);
        }
        return $stock_records;
    }

    /**
     * Update the stock
     * @param mixed $amount_to_return
     * @param float $new_amount
     * @param int $id
     * @param int $val
     * @param int $qty
     * @param string $type
     * @param mixed $needed_qty
     * @return string
     */
    private function update_stock_qty(int $needed_qty, AmountModel $amountModel): string {
        $this->update_amount($amountModel->get_id(), $amountModel->get_qty() - $needed_qty);
        return $this->format_line($amountModel->get_type(), $amountModel->get_value(), $needed_qty);
    }

    private function compute_due_amount($stock_list_by_value_desc, $stock_list_length, $amount_to_return, $is_decimal = false): string {
        try {
            $this->db_conn->beginTransaction();
            $amount_to_return_copy = $amount_to_return;
            $result = "";

            for ($i = 0; $i < $stock_list_length && $amount_to_return_copy > 0; $i++) {
                $val = $stock_list_by_value_desc[$i]['value'];
                $available_qty = $stock_list_by_value_desc[$i]['qty'];
                $needed_qty = $this->calculate_needed_qty($amount_to_return_copy, $val, $available_qty, $is_decimal);

                if ($needed_qty > 0) {
                    $amountModel = new AmountModel(
                        (int) $stock_list_by_value_desc[$i]['id'],
                        $val,
                        (int) $available_qty,
                        $stock_list_by_value_desc[$i]['type']
                    );
                    $result .= $this->update_stock_qty((int) $needed_qty, $amountModel);
                    $amount_to_return_copy -= $needed_qty * $val;
                }
            }

            $this->db_conn->commit();
        } catch (PDOException $ex) {
            handle_error(self::STOCK_UPDATE_ERROR_MSG, $ex);
            $this->db_conn->rollBack();
        }

        return $result;
    }

    private function calculate_needed_qty($amount_to_return, $val, $available_qty, $is_decimal): int {
        if ($amount_to_return < $val) {
            return 0;
        }

        $needed_qty = $is_decimal
            ? (int) floor(($amount_to_return * 100) / ($val * 100))
            : (int) floor($amount_to_return / $val);

        return min($needed_qty, $available_qty);
    }

    private function handle_edge_cases($loan, $debt): array {
        if (!isset($loan)
            || !isset($debt)
            || $debt <= 0) {
            $error_msg = $this->format_line_msg("Vous n'avez rien à payer !");
            throw new EdgeCaseException($error_msg);
        }
        if ($debt < $loan) {
            $error_msg = $this->format_line_msg("Le montant à payer ($debt €) ne peut être inférieur à l'emprunt ($loan €) effectué !");
            throw new EdgeCaseException($error_msg);
        }

        try {
            return $this->fetch_stock_order_by_desc();
        } catch (PDOException $ex) {
            $error_msg = $this->format_line_msg("Change indisponible, veuillez réessayer ultérieurement s'il vous plaît !");
        }

        throw new EdgeCaseException($error_msg);
    }

    public function handle_payment(): string {
        $loan = filter_var($_POST['loan'], FILTER_VALIDATE_FLOAT);
        $debt = filter_var($_POST['debt'], FILTER_VALIDATE_FLOAT);
        $stock_list_by_value_desc = $this->handle_edge_cases($loan, $debt);
        $stock_list_length = count($stock_list_by_value_desc);

        $result = "Stock indisponible";
        if ($stock_list_length > 0) {
            $due_amount = $debt - $loan;
            $amount_to_return = (int) floor($due_amount);
            $decimal_part_of_amount_to_return = $due_amount - $amount_to_return;

            $result = "<section><h2>Je dois vous rendre : </h2>";
            $result .= "<p class='col-orange m'>(Montant à payer: $debt €, Emprunt: $loan €)</p>";
            $result .= "<p class='col-orange m'>(À payer: Partie entière: $amount_to_return, Partie décimale: $decimal_part_of_amount_to_return)</p>";

            $result .= $this->compute_due_amount(
                $stock_list_by_value_desc,
                $stock_list_length,
                $amount_to_return);
            if ($decimal_part_of_amount_to_return > 0) {
                $result .= $this->compute_due_amount(
                    $stock_list_by_value_desc,
                    $stock_list_length,
                    $decimal_part_of_amount_to_return,
                    true);
            }
        }

        $result .= "</section>";
        return $result;
    }
}
