# ClimateShield AI — Video Demo Instructions
**For: Video production workmate**
**Target: 2-minute UNICEF Venture Fund pitch video**
**Recording tool: QuickTime (Mac) or OBS — 1920×1080, mic on**

---

## Accounts & URLs at a Glance

| What | Value |
|---|---|
| App URL | `http://localhost:8080` |
| Admin login | `admin@jarida.io` / `Admin@1234` |
| Nairobi parent | `grace@jarida.io` / `Admin@1234` |
| **Kisumu parent** | `akinyi@jarida.io` / `Admin@1234` ← use this for demo |
| Doctor | `doctor@jarida.io` / `Admin@1234` |
| ClimateShield engine | `/tmp/climateshield-ai/climate-engine/` |
| Dashboard HTML | Open `ClimateShield AI Dashboard.html` in Chrome |

---

## One-Time Setup (do this before recording)

```bash
# 1. Clone the merged repo
git clone https://github.com/jarida-io/Child-Immunization-Tracker.git
cd Child-Immunization-Tracker

# 2. Start the Docker stack
docker compose up -d

# Wait ~30 seconds for the database to be ready, then:

# 3. Load the video demo data
docker compose exec db mysql -u root cvs < docker/demo_seed.sql

# 4. Clone the ClimateShield AI engine
git clone https://github.com/jarida-io/climateshield-ai.git /tmp/climateshield-ai
pip3 install requests   # if not already installed

# 5. Verify the app opens
open http://localhost:8080
```

You should see the login page. If it shows a database error, wait 10 more seconds and refresh.

**Font size tip:** Before recording, set your terminal font to 16pt minimum (Terminal → Preferences → Profiles → Text). Use a dark terminal theme for better video contrast.

---

## Video Structure

| Time | Scene | Location |
|---|---|---|
| 0:00 – 0:30 | Problem statement (voiceover only, no screen) | — |
| 0:30 – 0:55 | Child Immunization Tracker — Guardian view | Browser |
| 0:55 – 1:05 | Admin dashboard — system-wide stats | Browser |
| 1:05 – 1:20 | Climate engine — terminal output | Terminal |
| 1:20 – 1:35 | ClimateShield AI Dashboard | Browser (HTML file) |
| 1:35 – 1:45 | SMS alert on phone / notification in-app | Browser |
| 1:45 – 2:00 | Call to action | — |

---

## Shot-by-Shot Script

---

### SCENE 1 — Problem Statement (0:00 – 0:30)
*Voiceover only. No screen required. Can record over a still of Kenya's map or the ClimateShield AI Dashboard splash screen.*

> "In Kenya, a child dies from a vaccine-preventable disease every few hours. Community health workers know which children are unvaccinated — but they don't know which diseases are about to surge in their county. ClimateShield AI changes that."

---

### SCENE 2 — Guardian Dashboard (0:30 – 0:55)

**Navigate to:** `http://localhost:8080`
**Log in as:** `akinyi@jarida.io` / `Admin@1234`

You will land on the Guardian dashboard showing two children: **Zuri Otieno** and **Kofi Otieno**, both in Kisumu.

**Say:**
> "This is the Child Immunization Tracker — an open-source system community health workers and parents in Kenya use to track every child's vaccination status against the national KEPI schedule."

Click **View Details** on **Zuri Otieno**.

You will see the full vaccination schedule. Scroll down slightly so the **Missed** dose rows are visible (OPV 2, DPT 2, PCV 2, RV 2 — all missed from July 2024 onward).

**Say:**
> "Zuri, aged 1, in Kisumu, has eight missed or pending doses. The system knows this — and it knows her mother's phone number."

Point to (or zoom in on) the red **Missed** status badges.

---

### SCENE 3 — Admin Dashboard (0:55 – 1:05)

**Log out**, then log in as `admin@jarida.io` / `Admin@1234`.

You will land on the Admin dashboard. It shows system-wide statistics: total children, vaccinations completed, pending, missed.

**Say:**
> "The admin and county health officer see the full picture — which children are at risk, across all guardians."

Click **Reports** in the navigation. The Children report shows all 4 registered children across Nairobi and Kisumu.

---

### SCENE 4 — Climate Engine Terminal (1:05 – 1:20)

**Switch to Terminal.**

```bash
cd /tmp/climateshield-ai/climate-engine
python3 ingest.py --demo
```

Let the output scroll. **Do not cut away.** Wait for the full output including the alerts section at the bottom.

The terminal will show risk scoring JSON for each county, ending with:

```
--- Alerts triggered ---
  ⚠  [HIGH] Cholera — Kisumu: SMS alerts queued for under-vaccinated children
  ⚠  [HIGH] Malaria — Kisumu: SMS alerts queued for under-vaccinated children
  ⚠  [HIGH] Malaria — Mombasa: SMS alerts queued for under-vaccinated children
  ⚠  [MEDIUM] Cholera — Mombasa: SMS alerts queued for under-vaccinated children
  ⚠  [MEDIUM] Pneumonia — Eldoret: SMS alerts queued for under-vaccinated children

5 alert(s) dispatched via Africa's Talking SMS gateway.
```

Scroll the terminal so the **alerts section is fully visible** before moving on.

**Say:**
> "ClimateShield AI pulls 14-day weather forecasts for every Kenyan county. It scores outbreak risk at three levels — High, Medium, and Low — for cholera, malaria, pneumonia, and meningitis. Right now it's flagging Kisumu as HIGH risk for both cholera and malaria. That forecast window is 7 to 14 days — enough time to act."

Pause for 2 seconds on the alert output before switching screens.

---

### SCENE 5 — ClimateShield AI Dashboard (1:20 – 1:35)

**Open** the file `ClimateShield AI Dashboard.html` in Chrome (double-click it from Finder, or drag it into Chrome).

The dashboard loads showing:
- Kenya county map with Kisumu in red, Mombasa in amber
- Active alerts feed (5 alerts with HIGH/MEDIUM badges)
- SMS preview panel on the right showing the alert message to Akinyi

**Say:**
> "The ClimateShield AI dashboard gives county health officers a live view of outbreak risk overlaid on every county. Red means act now. The system has already queried the immunization tracker, identified the under-vaccinated children in Kisumu, and queued SMS alerts — automatically."

Hover over (or point to) the **Kisumu** county on the map, then pan to the **SMS preview panel** on the right side.

---

### SCENE 6 — In-App Climate Alert / Notification (1:35 – 1:45)

**Switch back to the browser** at `http://localhost:8080`.

Log in as `akinyi@jarida.io` (the Kisumu parent). Click **Notifications** in the top navigation. The unread count badge will show **4 unread**.

The notification feed shows:
- 🚨 **CLIMATE ALERT: High cholera risk detected in Kisumu** — Zuri has 8 missed/pending doses
- 🚨 **CLIMATE ALERT: High malaria risk in Kisumu**
- ⚠ Missed vaccination alerts for Kofi

**Say:**
> "The parent receives the alert directly in-app and by SMS — on any phone, including feature phones via USSD. No smartphone required. The message tells them exactly which child, which missed vaccine, and where to go."

---

### SCENE 7 — Call to Action (1:45 – 2:00)
*Voiceover over a still of the dashboard or the ClimateShield AI logo.*

> "ClimateShield AI is open source, Apache 2.0, built by a youth-led team in Kenya. With UNICEF Venture Fund support, we will expand to all 47 counties, integrate with existing health information systems, and prevent the next outbreak before it happens. Visit jarida.io."

---

## Tips for a Clean Recording

1. **Run the seed script fresh** before recording: `docker compose exec db mysql -u root cvs < docker/demo_seed.sql` — this resets the data to a perfect demo state.
2. **Browser zoom:** Set Chrome to 90% zoom (Cmd + `-`) so more of the UI fits on screen.
3. **Hide browser bookmarks bar** before recording (Cmd + Shift + B).
4. **Terminal font 16pt minimum** — text must be readable after video compression.
5. **Close all other apps** — no notification pop-ups during recording.
6. **The `--demo` flag is instant** — no internet needed, no API keys, fully deterministic output every time.
7. If you need to re-record, just re-run the seed script and start again from Scene 2.
8. **Screen resolution:** Record at 1920×1080. If your display is different, set a virtual resolution in System Preferences → Displays.

---

## Files You Need

| File | Where to get it |
|---|---|
| `Child-Immunization-Tracker` repo | `git clone https://github.com/jarida-io/Child-Immunization-Tracker.git` |
| `climateshield-ai` repo | `git clone https://github.com/jarida-io/climateshield-ai.git` |
| `ClimateShield AI Dashboard.html` | Sent separately by Emmanuel |
| Wireframes / flowcharts | Sent separately by Emmanuel |

---

*Questions? Contact Emmanuel: oyugi@jarida.io*
