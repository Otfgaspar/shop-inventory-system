<?php
const BASE_URL = '/shop-inventory-system/public';

function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return 'GH₵ ' . number_format((float) $amount, 2);
}