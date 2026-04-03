# Multi-Language Support

SmartMoney supports English and Arabic with per-user language preferences.

## How It Works

- Each user has a `language` field (`en` or `ar`)
- On every page load, `app()->setLocale()` is called with the user's language preference
- All UI labels, alerts, notifications, and messages use Laravel's `__()` translation helper

## Translation Files

Located in `lang/`:

```
lang/
├── en/
│   ├── alert.php      # Alert titles and messages
│   ├── menu.php       # Navigation, labels, settings UI
│   └── widget.php     # Dashboard widget labels
└── ar/
    ├── alert.php      # Arabic alert translations
    ├── menu.php       # Arabic navigation/labels
    └── widget.php     # Arabic widget labels
```

## Localized Content

| Area | Description |
|------|-------------|
| Navigation | Sidebar labels, page titles, breadcrumbs |
| Dashboard widgets | Chart labels, table headers, stats |
| Alert messages | Transaction notifications, anomaly alerts, subscription alerts |
| Form labels | All Filament form fields and validation messages |
| Notification content | Web Push and email alert text |

## User Language Setting

- Set in the User management page (admin)
- Dropdown with `en` (English) and `ar` (العربية)
- Applies immediately on next page load

## Adding a New Language

1. Create a new directory under `lang/` (e.g., `lang/fr/`)
2. Copy all files from `lang/en/` and translate the values
3. Add the language option to the User form in `UserResource.php`:
   ```php
   Select::make('language')->options([
       'en' => 'English',
       'ar' => 'العربية',
       'fr' => 'Français',
   ])
   ```
