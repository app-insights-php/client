<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

interface ClientFactoryInterface
{
    public function create() : Client;
}