<?php

namespace Database\Seeders;

/** Explicit transaction reset; never called automatically or during deployment. */
class ClearTransactionsSeeder extends RebuildSimulationSeeder
{
    protected bool $seedSimulation = false;
    protected array $preservedTables = ['bill_of_material_items', 'bill_of_materials'];
}
