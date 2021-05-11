//The following will delete all visit and invoice data and associated documents
DELETE FROM srv_contract_visit WHERE visit_id > 0;
DELETE FROM srv_visit_user_assist WHERE assist_id > 0;
DELETE FROM srv_visit_item WHERE data_id > 0;

DELETE FROM srv_contract_invoice WHERE invoice_id > 0;
DELETE FROM srv_invoice_item WHERE item_id > 0;

DELETE FROM srv_file WHERE file_id > 0 AND (location_id LIKE "VST%" OR location_id LIKE "INV%");