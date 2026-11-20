<?php

return [
    'title' => 'Track Progress',
    'production' => 'Production Progress by Variant',
    'documents' => 'Purchasing and Material Issues',
    'mr' => 'Material Request (MR)',
    'pr' => 'Purchase Request (PR)',
    'po' => 'Purchase Order (PO)',
    'receipt' => 'Goods Receipt',
    'mis' => 'Material Issue to Production (MIS)',
    'item_quantities' => 'Quantity by Item',
    'target' => 'Order Target',
    'finished_goods' => 'Finished Goods',
    'remaining' => 'Finished Goods Shortfall',
    'not_started' => 'Production Not Started',
    'started_at' => 'Production started on :date',
    'target_met' => 'Finished Goods Target Met',
    'no_documents' => 'No related documents yet.',
    'statuses' => [
        'draft' => 'Draft',
        'pending' => 'Awaiting Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'issued' => 'Issued',
        'partially_received' => 'Partially Received',
        'received' => 'Fully Received',
        'cancelled' => 'Cancelled',
    ],
    'mis_statuses' => [
        'pending' => 'Awaiting Production Receipt',
        'received' => 'Received by Production',
    ],
];
