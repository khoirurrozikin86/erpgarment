<?php

namespace Database\Seeders;

/** Explicit reset with backup, without creating replacement sample data. */
class ClearTransactionsAndItemsSeeder extends RebuildSimulationSeeder
{
    protected bool $seedSimulation = false;
    protected array $additionalResetTables = ['product_images', 'products'];
}
