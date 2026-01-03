# Life OS Dashboard

Mfumo wa kusimamia maisha yako binafsi (Habits, Tasks, Finance, na Roadmap) uliojengwa kwa PHP na PostgreSQL.

## Vipengele (Features)
- **Habit Tracker:** Fuatilia tabia zako za kila siku.
- **Task Manager:** Orodha ya kazi za kufanya.
- **Finance Tracker:** Simamia mapato na matumizi.
- **Roadmap & Challenge:** Panga malengo ya muda mrefu.
- **Pomodoro Timer:** Saa ya kuzingatia kazi.
- **User System:** Login, Register, na Profile management.

## Jinsi ya Kuanza (Installation)

1. **Clone Repository:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/life-os-dashboard.git
   cd life-os-dashboard
   ```

2. **Database Setup:**
   - Tengeneza database mpya ya PostgreSQL inayoitwa `task_monitor`.
   - Copy faili la config:
     ```bash
     cp config/db.example.php config/db.php
     ```
   - Fungua `config/db.php` na uweke username na password yako ya database.

3. **Run Installer:**
   - Fungua browser na nenda: `http://localhost/life-os-dashboard/public/install.php`
   - Bonyeza "Run Installer" kutengeneza majedwali.

4. **Anza Kutumia:**
   - Nenda `public/index.php` na uanze kutumia mfumo!