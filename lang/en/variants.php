<?php

return [
    'stage_today' => 'Today',
    'stage_total' => 'Cumulative',
    'image_only_notice' => 'This MO is already used in transactions. You can still add or replace its image; other order fields are locked.',
    'image_updated_audit' => 'Updated image for Material Order :number',

    'upload_hint' => 'Upload JPG, PNG or WebP up to 5 MB. The image applies to all sizes in this submission. Leave empty when editing to retain the image.',
    'upload_failed' => 'Unable to save the MO image.',

    'matrix' => 'Enter multiple sizes',
    'matrix_hint' => 'Enter one color and quantity per size. Each size is saved under the same MO number. Missing item variants are created automatically.',
    'add_size' => '+ Add size',
    'duplicate_size' => 'Sizes must be unique within the same color.',
    'inactive' => 'This item variant is inactive. Reactivate it in the Item master.',

    'image' => 'Item image',
    'image_hint' => 'No image available. Choose a file to add an MO image.',
    'parent' => 'Parent item (optional)',
    'standalone' => 'Standalone / parent item',
    'invalid_parent' => 'Choose a root item. An item with variants cannot become a child.',
    'required_dimensions' => 'Enter a color or size for this variant.',
    'same_unit' => 'Variant unit and type must match its parent.',
    'duplicate' => 'This parent already has this color and size combination.',
    'used_identity' => 'This variant identity is used by transactions/BOM. Create a new SKU for another variant.',
    'applies_color' => 'Order color',
    'applies_size' => 'Order size',
    'all' => 'All',
    'selector_help' => 'Blank applies to all order variants. Matching rows are additive; enter consumption by size when it differs.',
    'parent_help' => 'Each physical variant uses a unique SKU and separate stock. Upload its image in the item images section.',
];
