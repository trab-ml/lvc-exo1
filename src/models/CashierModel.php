<?php
const INSERT_MSG_ERROR = "Error while inserting : ";
const SELECT_MSG_ERROR = "Error while fetching : ";
const UNEXPECTED_MSG_ERROR = "Unexpected error : ";

const MIN = 1, MAX = 10;

class CashierModel {
    private ?PDO $db_conn;
    private Array $amount_list;

    public function __construct(PDO $db_conn, Array $amount_list) {
        $this->db_conn = $db_conn;
        $this->amount_list = $amount_list;
    }

    public function initialize_stock(): void {
        foreach ($this->amount_list as $amount) {
            $this->insert_amount($amount);
        }
    }

    // TODO: prepared query
    public function insert_amount($amount): void {
        try {
            $value = (float) $amount['value'];
            $qty = rand(MIN, MAX);
            $type = $amount['type'];

            $insert = "INSERT INTO stock (value, qty, type) 
                            VALUES ('$value', '$qty', '$type')";
            $this->db_conn->exec($insert);
        } catch (PDOException $ex) {
            $this->handle_error(INSERT_MSG_ERROR, $ex);
        }
    }

    public function fetch_stock_order_by_desc(): array {
        $stock_records = [];
        try {
            $select = "SELECT qty, value, type FROM stock WHERE qty > 0 ORDER BY value DESC";
            $stock_records = $this->db_conn->query($select)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            $this->handle_error(SELECT_MSG_ERROR, $ex);
        }
        return $stock_records;
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
