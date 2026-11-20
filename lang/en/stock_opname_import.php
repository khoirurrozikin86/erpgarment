<?php

return [
    'title' => 'Import Physical Counts',
    'template' => 'Download Stocktake Template',
    'file' => 'Excel File (.xlsx)',
    'invalid_file' => 'The Excel file could not be read. Use the stocktake template.',
    'upload' => 'Import Counts',
    'help' => 'Enter one line per item or SKU. Review the counts before posting.',
    'one_sheet' => 'Use a single sheet for stocktake data.',
    'empty' => 'Enter at least one item and its physical quantity.',
    'unknown_item' => 'Item :code was not found or does not track stock.',
    'use_sku' => 'Parent item :code has variants. Enter the SKU code in Excel.',
    'duplicate_item' => 'Code :code appears more than once.',
    'row_error' => 'Row :row. :message',
    'success' => 'Counts imported. Review them before posting.',
];
