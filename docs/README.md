# Documentation — ClimateShield AI + Child Immunization Tracker

Diagrams and wireframes supporting the UNICEF Venture Fund grant application.

## Diagrams

All diagrams use [Mermaid](https://mermaid.js.org/) and render natively on GitHub.

| Diagram | Description |
|---|---|
| [System Architecture](diagrams/system-architecture.md) | Full system view — external APIs, climate engine, tracker, alert pipeline, end users |
| [Parent Alert Journey](diagrams/parent-alert-journey.md) | Sequence diagram: forecast → risk score → SMS → clinic visit |
| [Risk Scoring Logic](diagrams/risk-scoring.md) | How the engine decides HIGH/MEDIUM/LOW for each disease |
| [Data Flow](diagrams/data-flow.md) | Data contracts and transformation pipeline |

## Wireframes

SVG mockups of each key screen — open directly in a browser, Figma, or any vector editor.

| Wireframe | Screen |
|---|---|
| [Guardian Dashboard](wireframes/01-guardian-dashboard.svg) | Parent's home — children, progress, climate alert banner |
| [Child Vaccination Schedule](wireframes/02-child-vaccination-schedule.svg) | Per-child KEPI schedule with missed/pending status |
| [Admin Dashboard](wireframes/03-admin-dashboard.svg) | County health officer — KPIs, alerts table, coverage by county |
| [SMS + USSD](wireframes/04-sms-and-ussd.svg) | Parent-facing SMS and USSD menu (any phone, no internet) |

## Related repositories

- [climateshield-ai](https://github.com/jarida-io/climateshield-ai) — the climate engine, ML predictor, dashboard
- [kenyan_sign_language_app](https://github.com/jarida-io/kenyan_sign_language_app) — sister project, MediaPipe-based KSL Translator
- [.github (org profile)](https://github.com/jarida-io/.github) — Jarida Open Source landing page

## For the video team

See [`VIDEO_DEMO_INSTRUCTIONS.md`](../VIDEO_DEMO_INSTRUCTIONS.md) at the repo root for the full 2-minute pitch video script and shot list.
