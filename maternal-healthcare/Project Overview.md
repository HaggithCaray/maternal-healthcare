# Maternal Health Hub — Project Overview

## Purpose
A **maternal and child health monitoring system** for Barangay Bicao Health Station, Carmen, Bohol, Philippines. Designed for community health workers (midwives) and patients.

## Tech Stack
| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13.8, PHP 8.4 |
| Frontend | Tailwind CSS 4, Blade templates, Material Symbols |
| Database | MySQL 8.4 (via XAMPP) |
| Realtime | Laravel Reverb (WebSockets) |
| Offline | Service Worker + IndexedDB (PWA) |
| SMS | HTTP gateway integration |
| Server | XAMPP (Apache + MySQL + PHP 8.4) |

## Key Features
- **Patient Registration** — Multi-step wizard for maternal and child patients
- **Edit Patient** — Full edit form for personal info, maternal/child records
- **Maternal Health Tracking** — OB history, prenatal checkups, vital signs
- **Child Growth Monitoring** — Weight/height measurements, growth charts
- **Immunization Tracking** — 14-dose vaccine schedule with status management
- **SMS Messaging** — Gateway integration for appointment reminders
- **Real-time Chat** — WebSocket-based messaging between health workers and patients
- **Offline PWA** — Register patients offline, auto-sync when connected
- **Role-Based Access** — Admin (health worker) and Patient roles
- **Reports & Analytics** — Registration trends, vaccine compliance rates

## User Roles
| Role | Access |
|------|--------|
| **Admin** (Healthcare Worker) | Full access: records, registration, checkups, SMS, messaging, reports |
| **User** (Patient) | Limited: own records, messaging with midwife, portal dashboard |

## Design System
Material Design 3-inspired tokens with Philippine health branding:
- Primary: `#00478d` (blue)
- Secondary: `#006a6a` (teal)
- Tertiary: `#0d5400` (green)
- Font: Inter (400–700)

## Related Pages
- [[Setup Guide]]
- [[Architecture]]
- [[Entity Relationships]]
- [[Routes Map]]
