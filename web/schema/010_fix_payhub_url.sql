-- Update Payhub base URL to correct mock gateway endpoint
UPDATE `payment_settings` SET `payhub_base_url` = 'https://payhub.pmhserver.name.ng' WHERE `payhub_base_url` = 'https://api.payhub.com.ng' OR `payhub_base_url` IS NULL OR `payhub_base_url` = '';
