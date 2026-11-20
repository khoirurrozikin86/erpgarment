<?php

namespace Database\Seeders;

/** Explicit destructive reset. Never included in DatabaseSeeder or deployment. */
class ResetItemsForPoloSampleSeeder extends RebuildSimulationSeeder
{
    protected bool $seedSimulation = false;
    protected array $additionalResetTables = ['product_images', 'products'];

    protected function afterReset(): void
    {
        $this->call(PoloThreeCombinationSeeder::class);
    }
}
