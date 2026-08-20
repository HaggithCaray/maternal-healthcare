# Testing Guide

## Test Framework
- **Framework:** PHPUnit 12.5+ (via Laravel)
- **Test database:** SQLite in-memory (default)
- **Run command:** `php artisan test`

## Test Files

### tests/Unit/ExampleTest.php
| Test | Description |
|------|-------------|
| `test_that_true_is_true` | Basic sanity check |

### tests/Feature/AuthControllerTest.php (7 tests)
| Test | Description |
|------|-------------|
| `test_login_form_can_be_rendered` | GET `/` shows login form |
| `test_admin_can_login_successfully` | Admin login → `/dashboard` |
| `test_user_can_login_successfully` | Patient login → `/portal` |
| `test_user_cannot_login_with_invalid_credentials` | Wrong password fails |
| `test_user_cannot_login_with_incorrect_role` | Role mismatch fails |
| `test_user_can_logout` | Logout invalidates session |
| `test_admin_can_view_dashboard_with_statistics` | Dashboard counts correct |

### tests/Feature/PageControllerTest.php (22 tests)
| Test | Description |
|------|-------------|
| `test_guest_cannot_access_protected_routes` | 10 routes redirect to login |
| `test_guest_cannot_access_edit_patient` | Edit patient redirects |
| `test_admin_can_access_records_page` | Records page with correct stats |
| `test_admin_can_filter_and_search_records` | Search by name, filter by type |
| `test_admin_can_view_registration_form` | GET `/register` renders form |
| `test_admin_can_register_maternal_patient_with_user` | Full maternal registration |
| `test_admin_can_register_child_patient` | Full child registration + immunizations |
| `test_admin_can_view_edit_form_for_maternal_patient` | Edit form loads (200) |
| `test_admin_can_view_edit_form_for_child_patient` | Edit form loads (200) |
| `test_admin_can_update_maternal_patient` | Updates patient + maternal record |
| `test_admin_can_update_child_patient` | Updates patient + child record |
| `test_maternal_page_access_and_records` | Role-aware maternal view |
| `test_admin_can_add_maternal_checkup` | Admin creates checkup |
| `test_admin_can_add_growth_measurement` | Admin creates growth entry |
| `test_admin_can_update_immunization_status` | Mark vaccine as Given |
| `test_admin_can_send_sms_success` | SMS sent via mocked gateway |
| `test_admin_can_send_sms_failure` | SMS failure recorded |
| `test_admin_can_update_sms_settings` | Settings saved to JSON |
| `test_admin_can_check_sms_status` | Gateway status check |
| `test_messaging_flows` | Full send/read/reply cycle |
| `test_messaging_ajax_flows` | Same via JSON API |
| `test_message_with_image_upload` | Image attachment |
| `test_message_with_document_upload` | PDF attachment |
| `test_message_with_large_video_upload` | 15MB video |
| `test_admin_can_access_admin_panel` | Admin sees admin page |
| `test_user_cannot_access_admin_panel` | 403 for regular users |
| `test_online_presence_channel_authorization` | Presence channel auth |

### tests/Feature/SyncControllerTest.php (5 tests)
| Test | Description |
|------|-------------|
| `test_guests_cannot_access_sync_endpoints` | Unauth blocked |
| `test_token_endpoint_returns_csrf_token` | Returns token JSON |
| `test_non_admin_cannot_sync_registrations` | 403 for non-admin |
| `test_admin_can_sync_maternal_registration` | Sync creates maternal |
| `test_admin_can_sync_child_registration` | Sync creates child |
| `test_invalid_item_is_reported_as_error` | Error returned, no DB insert |
| `test_multiple_items_are_synced` | Batch sync works |

## Running Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test --filter="test_admin_can_update_maternal_patient"

# Specific class
php artisan test --filter="PageControllerTest"

# Verbose output
php artisan test --filter="..." -v
```

## Known Failure
- `test_online_presence_channel_authorization` — Fails because `Pusher\Pusher` class is not installed (Reverb uses its own driver). Pre-existing issue unrelated to app code.

## Related Pages
- [[Setup Guide]]
- [[Controllers]]
