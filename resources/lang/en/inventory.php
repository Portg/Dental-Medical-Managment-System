<?php

return [
    // Module
    'inventory_management' => 'Inventory Management',
    'categories' => 'Categories',
    'items' => 'Items',
    'stock_in' => 'Stock In',
    'stock_out' => 'Stock Out',
    'stock_warnings' => 'Stock Warnings',
    'expiry_warnings' => 'Expiry Warnings',
    'service_consumables' => 'Service Consumables',

    // Category Types
    'type_drug' => 'Drug',
    'type_consumable' => 'Consumable',
    'type_instrument' => 'Instrument',
    'type_dental_material' => 'Dental Material',
    'type_office' => 'Office Supply',

    // Category Fields
    'category' => 'Category',
    'category_name' => 'Category Name',
    'category_code' => 'Category Code',
    'category_type' => 'Category Type',
    'description' => 'Description',
    'sort_order' => 'Sort Order',

    // Item Fields
    'item' => 'Item',
    'item_code' => 'Item Code',
    'item_name' => 'Item Name',
    'specification' => 'Specification',
    'unit' => 'Unit',
    'brand' => 'Brand/Manufacturer',
    'reference_price' => 'Reference Price',
    'selling_price' => 'Selling Price',
    'track_expiry' => 'Track Expiry',
    'stock_warning_level' => 'Warning Level',
    'storage_location' => 'Storage Location',
    'current_stock' => 'Current Stock',
    'average_cost' => 'Average Cost',
    'notes' => 'Notes',

    // Stock Status
    'low_stock' => 'Low Stock',
    'in_stock' => 'In Stock',
    'out_of_stock' => 'Out of Stock',
    'shortage' => 'Shortage',

    // Stock In Fields
    'stock_in_no' => 'Stock In No.',
    'stock_in_date' => 'Stock In Date',
    'supplier' => 'Supplier',
    'batch_no' => 'Batch No.',
    'expiry_date' => 'Expiry Date',
    'production_date' => 'Production Date',
    'unit_price' => 'Unit Price',
    'quantity' => 'Quantity',
    'amount' => 'Amount',
    'total_amount' => 'Total Amount',
    'confirm_stock_in' => 'Confirm Stock In',

    // Stock Out Fields
    'stock_out_no' => 'Stock Out No.',
    'stock_out_date' => 'Stock Out Date',
    'out_type' => 'Out Type',
    'out_type_treatment' => 'Treatment Consumption',
    'out_type_department' => 'Department Requisition',
    'out_type_damage' => 'Damage/Loss',
    'out_type_other' => 'Other',
    'department' => 'Department',
    'unit_cost' => 'Unit Cost',
    'confirm_stock_out' => 'Confirm Stock Out',

    // Status
    'status' => 'Status',
    'status_draft' => 'Draft',
    'status_confirmed' => 'Confirmed',
    'status_cancelled' => 'Cancelled',

    // Expiry Warnings
    'expired' => 'Expired',
    'expiring_soon' => 'Expiring Soon',
    'near_expiry' => 'Near Expiry',
    'days_to_expiry' => 'Days to Expiry',
    'warning_days' => 'Warning Days',

    // Service Consumables
    'service' => 'Service',
    'consumable' => 'Consumable',
    'required' => 'Required',
    'optional' => 'Optional',
    'consumable_qty' => 'Consumption Qty',

    // Messages
    'category_added_successfully' => 'Category added successfully',
    'category_updated_successfully' => 'Category updated successfully',
    'category_deleted_successfully' => 'Category deleted successfully',
    'category_has_items' => 'Cannot delete category with items',

    'item_added_successfully' => 'Item added successfully',
    'item_updated_successfully' => 'Item updated successfully',
    'item_deleted_successfully' => 'Item deleted successfully',
    'item_has_movements' => 'Cannot delete item with stock movements',

    'stock_in_created_successfully' => 'Stock in record created successfully',
    'stock_in_updated_successfully' => 'Stock in record updated successfully',
    'stock_in_deleted_successfully' => 'Stock in record deleted successfully',
    'stock_in_confirmed' => 'Stock in confirmed successfully',
    'stock_in_cancelled' => 'Stock in cancelled successfully',

    'stock_out_created_successfully' => 'Stock out record created successfully',
    'stock_out_updated_successfully' => 'Stock out record updated successfully',
    'stock_out_deleted_successfully' => 'Stock out record deleted successfully',
    'stock_out_confirmed' => 'Stock out confirmed successfully',
    'stock_out_cancelled' => 'Stock out cancelled successfully',

    'consumable_added_successfully' => 'Service consumable added successfully',
    'consumable_updated_successfully' => 'Service consumable updated successfully',
    'consumable_deleted_successfully' => 'Service consumable deleted successfully',
    'consumable_already_exists' => 'This consumable is already configured for the service',

    // Validation Messages
    'service_required' => 'Please select a service',
    'item_required' => 'Please select an item',
    'qty_required' => 'Please enter quantity',
    'qty_min' => 'Quantity must be greater than 0',
    'category_required' => 'Please select a category',
    'category_name_required' => 'Please enter category name',
    'category_code_required' => 'Please enter category code',
    'category_code_unique' => 'Category code already exists',
    'category_type_required' => 'Please select category type',
    'item_code_required' => 'Please enter item code',
    'item_code_unique' => 'Item code already exists',
    'item_name_required' => 'Please enter item name',
    'unit_required' => 'Please enter unit',
    'stock_in_date_required' => 'Please select stock in date',
    'stock_out_date_required' => 'Please select stock out date',
    'out_type_required' => 'Please select out type',
    'unit_price_required' => 'Please enter unit price',
    'batch_expiry_required' => 'Batch number and expiry date are required for this item',
    'price_deviation_warning' => 'Unit price deviates more than 20% from reference price. Please confirm.',
    'insufficient_stock' => 'Insufficient stock for :item',
    'cannot_edit_confirmed' => 'Cannot edit confirmed record',
    'cannot_delete_confirmed' => 'Cannot delete confirmed record',
    'cannot_confirm' => 'Cannot confirm this record',
    'cannot_cancel' => 'Cannot cancel this record',
    'no_items_to_confirm' => 'No items to confirm',

    // Actions
    'add_item' => 'Add Item',
    'add_category' => 'Add Category',
    'create_stock_in' => 'Create Stock In',
    'create_stock_out' => 'Create Stock Out',
    'confirm' => 'Confirm',
    'cancel_record' => 'Cancel',
    'view_details' => 'View Details',
    'configure_consumables' => 'Configure Consumables',

    // Report Headers
    'low_stock_warning' => 'Low Stock Warning',
    'expiry_warning' => 'Expiry Warning',
    'select_category' => 'Select Category',
    'select_supplier' => 'Select Supplier',
    'select_service' => 'Select Service',
    'select_item' => 'Select Item',
    'select_type' => 'Select Type',
    'filter' => 'Filter',
    'items_count' => 'Items Count',

    // Table Headers
    'sn' => 'S/N',
    'action' => 'Action',
    'added_by' => 'Added By',
    'created_at' => 'Created At',
];
