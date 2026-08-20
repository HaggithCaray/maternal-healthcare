# Design System

## Overview
Material Design 3 (MD3)-inspired design system with Philippine health branding. Implemented via Tailwind CSS 4 `@theme` directive.

## Color Palette

### Primary (Blue)
| Token | Hex | Usage |
|-------|-----|-------|
| `--primary` | `#00478d` | Buttons, links, active states |
| `--on-primary` | `#FFFFFF` | Text on primary |
| `--primary-container` | `#D6E3FF` | Light blue containers |
| `--on-primary-container` | `#001C3B` | Text on primary container |

### Secondary (Teal)
| Token | Hex | Usage |
|-------|-----|-------|
| `--secondary` | `#006a6a` | Secondary actions |
| `--on-secondary` | `#FFFFFF` | Text on secondary |
| `--secondary-container` | `#6FF7F6` | Light teal containers |

### Tertiary (Green)
| Token | Hex | Usage |
|-------|-----|-------|
| `--tertiary` | `#0d5400` | Success states |
| `--on-tertiary` | `#FFFFFF` | Text on tertiary |
| `--tertiary-container` | `#96F87A` | Light green containers |

### Surface
| Token | Hex | Usage |
|-------|-----|-------|
| `--surface` | `#FAFDFE` | Page background |
| `--surface-container` | `#EEF1F4` | Card backgrounds |
| `--on-surface` | `#1A1C1E` | Body text |
| `--on-surface-variant` | `#44474E` | Secondary text |

## Typography
- **Font:** Inter (400, 500, 600, 700)
- **Icons:** Material Symbols Outlined
- **Headline:** `font-headline-sm` → `text-headline-sm`
- **Body:** `text-body-md`, `text-body-sm`
- **Label:** `font-label-md` → `text-label-md`

## Spacing Scale
| Token | Value |
|-------|-------|
| `xs` | 4px |
| `sm` | 12px |
| `base` | 8px |
| `md` | 24px |
| `lg` | 40px |
| `xl` | 64px |
| `gutter` | 24px |

## Component Patterns

### Cards
```html
<div class="bg-surface-container rounded-xl p-md shadow-sm">
  Content
</div>
```

### Buttons
```html
<button class="bg-primary text-on-primary rounded-full px-lg py-sm font-label-lg hover:opacity-90">
  Action
</button>
```

### Form Inputs
```html
<input class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base">
```

### Status Badges
```html
<span class="px-sm py-xs rounded-full text-xs font-medium bg-primary-container text-on-primary-container">
  Active
</span>
```

## Layout Structure
```
┌──────────────────────────────────────┐
│  Header (search, notifications, menu) │
├──────┬───────────────────────────────┤
│ Side │  Main Content Area            │
│ bar  │  (scrollable)                 │
│      │                               │
│ Nav  │  ┌─ Cards ──────────────────┐ │
│      │  │ KPI / Stats              │ │
│      │  └─────────────────────────┘ │
│      │  ┌─ Table ─────────────────┐ │
│      │  │ Records / Data          │ │
│      │  └─────────────────────────┘ │
├──────┴───────────────────────────────┤
│  Footer / Watermark                  │
└──────────────────────────────────────┘
```

## Watermark
- Barangay Bicao + DOH logos overlaid at bottom-right
- Fixed positioning, semi-transparent

## Related Pages
- [[Views Map]]
- [[Project Overview]]
