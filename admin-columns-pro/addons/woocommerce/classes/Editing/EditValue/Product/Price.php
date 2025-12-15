<?php

namespace ACA\WC\Editing\EditValue\Product;

class Price
{

    private string $type;

    private string $price_type;

    private string $price;

    private string $percentage;

    private bool $rounding;

    private string $rounding_type = '';

    private int $rounding_decimals = 0;

    public function __construct(array $value)
    {
        $this->type = $value['type'];
        $this->price_type = $value['price']['type'];
        $this->price = $value['price']['value'];

        $this->percentage = (float)$value['price']['value'];
        $this->rounding = $value['rounding']['active'] === 'true';

        if ($this->rounding) {
            $this->rounding_type = $value['rounding']['type'];
            $this->rounding_decimals = absint($value['rounding']['decimals']);
        }
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function get_price_type(): string
    {
        return $this->price_type;
    }

    public function get_price(): string
    {
        return $this->price;
    }

    public function get_percentage(): float
    {
        return $this->percentage;
    }

    public function is_rounded(): bool
    {
        return $this->rounding;
    }

    public function get_rounding_type(): string
    {
        return $this->rounding_type;
    }

    public function get_rounding_decimals(): int
    {
        return $this->rounding_decimals;
    }

}