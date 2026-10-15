# Routes Map

## Web Routes (routes/web.php)

### Auth
| Method | URI | Name | Middleware | Description |
|--------|-----|------|-----------|-------------|
| GET | `/` | `login` | guest | Login form |
| POST | `/` | — | guest | Process login |
| POST | `/logout` | `logout` | auth | Logout |

### Dashboard
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/dashboard` | `dashboard` | Admin dashboard with KPIs |

### Patient Records
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/records` | `records` | Patient records list with search/filter |
| GET | `/records/export` | `export.patients` | CSV masterlist carrying the current filters (admin via `viewAny`) |
| GET | `/register` | `register` | Multi-step registration form |
| POST | `/register` | — | Process registration |
| GET | `/patients/{patient}/edit` | `patients.edit` | Edit patient form |
| PUT | `/patients/{patient}` | `patients.update` | Update patient |
| PATCH | `/patients/{patient}/archive` | `patients.archive` | Archive patient — hidden from lists, search, dashboard, exports until restored |
| PATCH | `/patients/{patient}/restore` | `patients.restore` | Restore an archived patient as Active |
| DELETE | `/patients/{patient}` | `patients.destroy` | Permanent delete — archived patients only, refused while linked children exist |

### Health Modules
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/maternal` | `maternal` | Maternal health records + postpartum section |
| POST | `/maternal` | — | Add prenatal checkup, or a postpartum visit when `visit_type=postpartum` |
| GET | `/growth` | `growth` | Child growth tracking |
| POST | `/growth` | — | Add growth measurement |
| GET | `/immunization` | `immunization` | Immunization timeline |
| POST | `/immunization` | — | Mark vaccine as given |

### Communication
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/sms` | `sms` | SMS center |
| POST | `/sms` | — | Send SMS |
| POST | `/sms/settings` | `sms.settings` | Update gateway settings + reminders switch |
| GET | `/sms/status` | `sms.status` | Test gateway connection |
| GET | `/messaging` | `messaging` | Chat interface |
| POST | `/messaging` | — | Send message |

### Reports & Admin
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/reports` | `reports` | Analytics & reports |
| GET | `/reports/export/summary` | `export.summary` | CSV report summary (admin) |
| GET | `/reports/export/prenatal` | `export.prenatal` | CSV prenatal visit log (admin) |
| GET | `/reports/export/postpartum` | `export.postpartum` | CSV postpartum visit log (admin) |
| GET | `/reports/export/immunizations` | `export.immunizations` | CSV immunization log (admin) |
| GET | `/admin` | `admin` | Admin panel (role:admin) |

### Patient Portal
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/portal` | `patient.portal` | Patient dashboard |

### Sync helpers (web)
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/sync/token` | `sync.token` | CSRF token used by `offline.js` |

## API Routes (routes/api.php)
All under `web` + `auth` middleware, prefix `/api/sync`:
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/api/sync/token` | `api.sync.token` | CSRF token for sync |
| POST | `/api/sync/patients` | `api.sync.patients` | Offline patient sync |
| POST | `/api/sync/batch` | `api.sync.batch` | Batch sync: checkups, growth, vaccines, postpartum visits |

## Broadcast Channels (routes/channels.php)
| Channel | Type | Auth |
|---------|------|------|
| `App.Models.User.{id}` | private | user.id === {id} |
| `conversation.{conversationId}` | private | user.id in conversation ID |
| `online` | presence | returns user id+name |

## Related Pages
- [[Architecture]]
- [[Controllers]]
