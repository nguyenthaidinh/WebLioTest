<?php
function recharge_bonus_tiers() {
    return [
        1000000 => 70,
        500000 => 50,
        300000 => 30,
        200000 => 20,
        100000 => 10,
    ];
}

function recharge_bonus_rate($amount) {
    $amount = (int)$amount;
    foreach (recharge_bonus_tiers() as $threshold => $rate) {
        if ($amount >= $threshold) {
            return $rate;
        }
    }
    return 0;
}

function recharge_bonus_amount($amount) {
    return intdiv((int)$amount * recharge_bonus_rate($amount), 100);
}

function recharge_credit_amount($amount) {
    return (int)$amount + recharge_bonus_amount($amount);
}
