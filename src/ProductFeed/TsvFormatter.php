<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

final class TsvFormatter extends CsvFormatter
{
    public function __construct()
    {
        parent::__construct("\t", 'text/tab-separated-values');
    }
}

