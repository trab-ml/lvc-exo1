<?php
class AmountModel {
    private int $id;
    private float $value;
    private int $qty;
    private string $type;
    
    public function __construct(int $id, float $value, int $qty, string $type) {
        $this->id = $id;
        $this->value = $value;
        $this->qty = $qty;
        $this->type = $type;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id(int $id) {
        $this->id = $id;
    }

    public function get_value() {
        return $this->value;
    }

    public function set_value(float $value) {
        $this->value = $value;
    }

    public function get_qty() {
        return $this->qty;
    }

    public function set_qty(int $qty) {
        $this->qty = $qty;
    }

    public function get_type() {
        return $this->type;
    }

    public function set_type(int $type) {
        $this->type = $type;
    }
}
