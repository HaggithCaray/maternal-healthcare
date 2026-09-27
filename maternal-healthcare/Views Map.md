# Views Map

## Layouts
| File | Purpose |
|------|---------|
| `layouts/app.blade.php` | Main authenticated layout: sidebar nav, header, user menu, FAB, watermark |
| `layouts/guest.blade.php` | Unauthenticated layout: centered, animated background |

## Auth
| File | Purpose |
|------|---------|
| `auth/login.blade.php` | Login with role selector, email/password, decorative illustration |

## Admin Views
| File | Purpose |
|------|---------|
| `dashboard.blade.php` | KPI cards, vaccination schedule, recent activity, health tips |
| `records.blade.php` | Patient records table with search/filter, Quick Actions dropdown |
| `register.blade.php` | 5-step registration wizard |
| `maternal.blade.php` | Maternal record: OB history, checkup timeline, add checkup form |
| `growth.blade.php` | Growth tracking: chart, history table, add measurement form |
| `immunization.blade.php` | Immunization timeline with status badges, mark-as-given form |
| `sms.blade.php` | SMS gateway settings, send form, message history log |
| `messaging.blade.php` | Chat: patient sidebar + unread badges, message bubbles, file attachments |
| `reports.blade.php` | Analytics: registration trends, vaccine compliance (note: "Export Report" button in the UI is a non-functional placeholder — PDF/CSV export was descoped, see [[Milestones]]) |
| `admin.blade.php` | System info, user management table |

## Patient Views
| File | Purpose |
|------|---------|
| `patient/portal.blade.php` | Patient home: summary cards, module cards |
| `patient/edit.blade.php` | Edit form: personal info, contact, maternal/child details |
| `patient/maternal.blade.php` | Read-only maternal record view |
| `patient/growth.blade.php` | Read-only growth chart and measurements |
| `patient/immunization.blade.php` | Read-only immunization timeline |
| `patient/messaging.blade.php` | Chat with midwife (patient perspective) |

## Design Tokens (used in all views)
```css
/* Colors (MD3-inspired) */
--primary: #00478d;        /* Blue */
--secondary: #006a6a;      /* Teal */
--tertiary: #0d5400;       /* Green */
--surface: #FAFDFE;
--on-surface: #1A1C1E;

/* Spacing */
--spacing-xs: 4px;
--spacing-sm: 12px;
--spacing-base: 8px;
--spacing-md: 24px;
--spacing-lg: 40px;
--spacing-xl: 64px;
--spacing-gutter: 24px;
```

## Related Pages
- [[Architecture]]
- [[Controllers]]
