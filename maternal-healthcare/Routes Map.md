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
| GET | `/register` | `register` | Multi-step registration form |
| POST | `/register` | — | Process registration |
| GET | `/patients/{patient}/edit` | `patients.edit` | Edit patient form |
| PUT | `/patients/{patient}` | `patients.update` | Update patient |

### Health Modules
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/maternal` | `maternal` | Maternal health records |
| POST | `/maternal` | — | Add maternal checkup |
| GET | `/growth` | `growth` | Child growth tracking |
| POST | `/growth` | — | Add growth measurement |
| GET | `/immunization` | `immunization` | Immunization timeline |
| POST | `/immunization` | — | Mark vaccine as given |

### Communication
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/sms` | `sms` | SMS center |
| POST | `/sms` | — | Send SMS |
| POST | `/sms/settings` | `sms.settings` | Update gateway settings |
| GET | `/sms/status` | `sms.status` | Test gateway connection |
| GET | `/messaging` | `messaging` | Chat interface |
| POST | `/messaging` | — | Send message |

### Reports & Admin
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/reports` | `reports` | Analytics & reports |
| GET | `/admin` | `admin` | Admin panel (role:admin) |

### Patient Portal
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/portal` | `patient.portal` | Patient dashboard |

## API Routes (routes/api.php)
| Method | URI | Name | Description |
|--------|-----|------|-------------|
| POST | `/api/sync/patients` | `api.sync.patients` | Offline patient sync |
| GET | `/sync/token` | `sync.token` | CSRF token for sync |

## Broadcast Channels (routes/channels.php)
| Channel | Type | Auth |
|---------|------|------|
| `App.Models.User.{id}` | private | user.id === {id} |
| `conversation.{conversationId}` | private | user.id in conversation ID |
| `online` | presence | returns user id+name |

## Related Pages
- [[Architecture]]
- [[Controllers]]
