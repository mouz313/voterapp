# 🗳️ VoterApp Mobile REST API Documentation (v1)

> **Pakistan Election Voter Verification Platform &bull; Mobile Polling Station Management System**  
> Complete Offline-First Architecture &bull; Candidate Multi-Device Management &bull; ECP Delimitation (NA, PP, UC, Census Block Codes, Polling Stations).

---

## 📌 1. System Overview & Architecture

VoterApp is designed for candidates and their polling agents operating on election day. Due to potential internet shutdown or weak signals at polling stations, the mobile application works in an **Offline-First Mode**:

1. **Authentication:** Polling agent logs into the mobile app using the candidate's account credentials and binds their physical device (`device_uid`).
2. **One-Time UC Sync:** The mobile app downloads the complete dataset of the candidate's Union Council (Voters, Silsala #, Gharana #, Census Block Codes with Urdu area names, and Polling Stations).
3. **Local SQLite Storage:** The dataset is indexed in the mobile phone's local SQLite database.
4. **100% Offline Search:** Polling agents search voters by CNIC, Name, or Family (Gharana) Number instantaneously without internet.
5. **Background Telemetry Sync:** When the device reconnects to internet (WiFi / 4G), it silently uploads search telemetry logs to update the candidate's live Operations Matrix.

---

## 🌐 2. Base URLs & Headers

| Environment | Base URL |
|---|---|
| **Standard API** | `http://127.0.0.1:8000/api/v1` |
| **Direct Route (without `/api/`)** | `http://127.0.0.1:8000/v1` |
| **Production Server** | `https://your-domain.com/api/v1` |

### Common Request Headers:
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <TOKEN_RECEIVED_FROM_LOGIN>
```

---

## 📑 3. Endpoints Quick Matrix

| # | Endpoint | Method | Purpose | Auth Required |
|---|---|:---:|---|:---:|
| **1** | `/api/v1/auth/login` | `POST` / `GET` | Candidate worker login & device binding | No |
| **2** | `/api/v1/auth/check-device` | `GET` | Check if device is active or revoked | Yes |
| **3** | `/api/v1/download` | `GET` | Download full UC dataset for offline use | Yes |
| **4** | `/api/v1/sync/searches` | `POST` | Batch upload offline search telemetry logs | Yes |
| **5** | `/api/v1/sync/heartbeat` | `POST` | Device live ping & battery telemetry | Yes |
| **6** | `/api/v1/voters/search` | `POST` / `GET` | Direct online voter search fallback | Yes |
| **7** | `/api/ucs` | `GET` | Raw list of all UCs, Tehsils & Districts | Optional |
| **8** | `/api/ucs/{id}/block-codes` | `GET` | List census block codes of a UC | Optional |
| **9** | `/api/ucs/{id}/polling-stations`| `GET` | List polling stations of a UC | Optional |
| **10**| `/api/ucs/{id}/voters` | `GET` | Paginated voters list of a UC | Optional |

---

## 🔑 4. Detailed API Specifications

### 1. Candidate Login & Device Authorization
Authenticates candidate worker credentials, registers the physical mobile device hardware UID, and returns authorization token, party branding, and assigned UC.

- **URL:** `POST /api/v1/auth/login` *(or `POST /v1/auth/login`)*
- **Browser Quick-Test (GET):**  
  `GET /api/v1/auth/login?email=usman@gmail.com&password=yourpassword&device_uid=3a4b5c6d-7e8f-90ab&device_name=Samsung Galaxy S23&platform=android`

#### Request Parameters:
| Parameter | Type | Required | Description |
|---|---|:---:|---|
| `email` | `string` | **Yes** | Candidate account email |
| `password` | `string` | **Yes** | Candidate account password |
| `device_uid` | `string` | **Yes** | Unique hardware device ID (UUID / Android ID / IMEI) |
| `device_name`| `string` | *Optional*| Device model name (e.g., `Samsung Galaxy A54`, `Redmi Note 12`) |
| `platform` | `string` | *Optional*| `android` or `ios` (default: `android`) |
| `app_version`| `string` | *Optional*| App release version (e.g., `1.0.0`) |

#### Sample Request Body (JSON):
```json
{
  "email": "usman@gmail.com",
  "password": "yourpassword",
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "device_name": "Infinix Hot 30",
  "platform": "android",
  "app_version": "1.0.0"
}
```

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Login successful. Device authorized.",
  "token": "MjozYTRiNWM2ZC03ZThmLTkwYWI6MTc4ODQzNDExMDo3MzI3MTc5ZTIyZWUxZWMxZWYzOTUxMzgwZTNlOTMwMDkzN2UzMGE0",
  "candidate": {
    "id": 2,
    "name": "Usman Saeed",
    "email": "usman@gmail.com",
    "phone": "03224028090",
    "max_devices": null,
    "active_devices": 1
  },
  "branding": {
    "party_name": "PTI",
    "is_independent": false,
    "candidate_symbol": "Ball",
    "party_logo_url": "http://127.0.0.1:8000/uploads/branding/party_1788433324_6a9953ace06bb.png",
    "candidate_image_url": "http://127.0.0.1:8000/uploads/branding/candidate_1788433324_6a9953ace105d.png",
    "candidate_symbol_image_url": "http://127.0.0.1:8000/uploads/branding/symbol_1788433324_6a9953ace18d2.png"
  },
  "uc": {
    "id": 75,
    "uc_no": "1",
    "name": "Shadi Pura",
    "tehsil": "Allama Iqbal Town",
    "district": "لاہور",
    "national_assembly": {
      "id": 1,
      "code": "NA-122",
      "name": "Lahore VI"
    },
    "provincial_assembly": {
      "id": 5,
      "code": "PP-158",
      "name": "Lahore XV"
    }
  },
  "server_time": "2026-09-03T11:15:11+00:00"
}
```

#### Error Responses:
- **`401 Unauthorized`**: Invalid credentials
  ```json
  { "status": false, "message": "Invalid email or password." }
  ```
- **`403 Forbidden`**: Candidate account suspended or Device revoked
  ```json
  { "status": false, "message": "This candidate account is currently suspended." }
  ```
- **`422 Unprocessable Content`**: Missing required parameters
  ```json
  {
    "status": false,
    "message": "Validation failed.",
    "errors": { "device_uid": ["The device uid field is required."] }
  }
  ```

---

### 2. Check Device Authorization Status
Verifies if this physical mobile device is still authorized or if access has been revoked by the administrator.

- **URL:** `GET /api/v1/auth/check-device`
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Query Params (Optional):** `?device_uid=9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d`

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "is_authorized": true,
  "device_name": "Samsung Galaxy S23",
  "last_active_at": "2026-09-03T12:00:00Z"
}
```

#### Revoked Response (`403 Forbidden`):
```json
{
  "status": false,
  "is_authorized": false,
  "message": "This device access has been revoked/blocked by the administrator."
}
```

---

### 3. Download Full UC Offline Dataset
Downloads all Voters, Census Block Codes (with English and Urdu area names), and Polling Stations for the candidate's assigned Union Council in a single payload.

- **URL:** `GET /api/v1/download` *(or `GET /api/v1/uc/{uc_id}/download`)*
- **Headers:** `Authorization: Bearer <TOKEN>` *(Required)*
- **Query Params:** `?uc_id=75` *(Optional; strictly verified against candidate's assigned UC)*

> **🔒 Security & Per-UC Authorization:**
> - Request **MUST** contain a valid device session token issued at `/auth/login`.
> - The dataset is strictly scoped to the candidate's assigned Union Council.
> - Attempting to download data for any other UC returns **`403 Forbidden`**.

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "UC Dataset downloaded successfully.",
  "uc": {
    "id": 75,
    "uc_no": "1",
    "name": "Shadi Pura",
    "name_ur": "شادی پورہ",
    "tehsil": "Allama Iqbal Town",
    "district": "لاہور"
  },
  "total_voters": 14500,
  "block_codes": [
    {
      "id": 1,
      "code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "area_name_ur": "بند روڈ شادی پورہ گلزار مدینہ موتی مسجد",
      "population": 2857
    }
  ],
  "polling_stations": [
    {
      "id": 10,
      "name": "Govt High School Shadi Pura (Male)",
      "address": "Main Bazar Shadi Pura",
      "total_voters": 1850
    }
  ],
  "voters": [
    {
      "id": 101,
      "silsala_no": "1",
      "gharana_no": "12",
      "name": "Muhammad Ali",
      "father_name": "Muhammad Aslam",
      "cnic": "3520112345671",
      "formatted_cnic": "35201-1234567-1",
      "age": 42,
      "gender": "male",
      "address": "Street 4, Shadi Pura",
      "block_code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "area_name_ur": "بند روڈ شادی پورہ گلزار مدینہ موتی مسجد",
      "polling_station_name": "Govt High School Shadi Pura"
    }
  ]
}
```

---

### 4. Silent Search Telemetry Sync
When the mobile device reconnects to WiFi/Data, it silently flushes offline search logs to the server.

- **URL:** `POST /api/v1/sync/searches`
- **Headers:** `Authorization: Bearer <TOKEN>`

#### Request Body (JSON):
```json
{
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "searches": [
    {
      "query_type": "cnic",
      "search_term": "35201-1234567-1",
      "results_count": 1,
      "searched_at": "2026-09-03 14:22:10"
    },
    {
      "query_type": "name",
      "search_term": "Muhammad Ali",
      "results_count": 4,
      "searched_at": "2026-09-03 14:25:34"
    },
    {
      "query_type": "gharana",
      "search_term": "12",
      "results_count": 8,
      "searched_at": "2026-09-03 14:29:12"
    }
  ]
}
```

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Searches telemetry synced successfully.",
  "synced_count": 3
}
```

---

### 5. Silent Device Heartbeat
Periodic background ping (sent every 15–30 minutes when internet is available) to update the device status on the Admin Operations Matrix.

- **URL:** `POST /api/v1/sync/heartbeat`
- **Headers:** `Authorization: Bearer <TOKEN>`

#### Request Body (JSON):
```json
{
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "battery_level": 82,
  "app_version": "1.0.0"
}
```

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "message": "Heartbeat acknowledged.",
  "server_time": "2026-09-03T12:30:00Z"
}
```

---

### 6. Live Online Voter Search Fallback
Real-time server search fallback when an agent wants to query directly over the network.

- **URL:** `POST /api/v1/voters/search` *(or `GET /api/v1/voters/search`)*
- **Headers:** `Authorization: Bearer <TOKEN>`

#### Query / Body Parameters:
| Field | Type | Required | Description |
|---|---|:---:|---|
| `q` or `cnic` | `string` | **Yes** | 13-digit CNIC (with/without dashes), voter name, or family number |
| `uc_id` | `integer` | *Optional* | Restrict lookup to a specific UC |

#### Success Response (`200 OK`):
```json
{
  "status": true,
  "count": 1,
  "voters": [
    {
      "id": 105,
      "silsala_no": "24",
      "gharana_no": "8",
      "name": "Muhammad Tariq",
      "father_name": "Abdul Rashid",
      "cnic": "3520112345678",
      "formatted_cnic": "35201-1234567-8",
      "age": 38,
      "gender": "male",
      "address": "Street 2, Shadi Pura",
      "block_code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "area_name_ur": "بند روڈ شادی پورہ گلزار مدینہ موتی مسجد",
      "polling_station_name": "Govt High School Shadi Pura"
    }
  ]
}
```

---

## 🏛️ 5. Direct / Headless REST Resources

| Endpoint | Method | Response |
|---|:---:|---|
| `/api/ucs` | `GET` | List of all Union Councils with Tehsil and District names |
| `/api/ucs/{uc_id}/block-codes` | `GET` | List of Census Block Codes & Electoral Areas for a UC |
| `/api/ucs/{uc_id}/polling-stations` | `GET` | List of Polling Stations & Booth assignments for a UC |
| `/api/ucs/{uc_id}/voters` | `GET` | Paginated voters list for a specific UC |

---

## 📱 6. Mobile App Recommended SQLite Schema

When building the mobile app (Flutter / React Native / Android Native / iOS Native), create these local SQLite tables:

```sql
-- 1. Candidate & Party Profile
CREATE TABLE candidate_profile (
    id INTEGER PRIMARY KEY,
    name TEXT,
    email TEXT,
    phone TEXT,
    party_name TEXT,
    candidate_symbol TEXT,
    party_logo_path TEXT,
    candidate_image_path TEXT,
    symbol_image_path TEXT,
    uc_name TEXT,
    tehsil_name TEXT,
    district_name TEXT
);

-- 2. Local Offline Voters Table
CREATE TABLE voters (
    id INTEGER PRIMARY KEY,
    silsala_no TEXT,
    gharana_no TEXT,
    name TEXT,
    father_name TEXT,
    cnic TEXT,
    formatted_cnic TEXT,
    age INTEGER,
    gender TEXT,
    address TEXT,
    block_code TEXT,
    area_name TEXT,
    area_name_ur TEXT,
    polling_station_name TEXT
);

-- Optimized Indexes for Sub-second Offline Search
CREATE INDEX idx_voters_cnic ON voters(cnic);
CREATE INDEX idx_voters_name ON voters(name);
CREATE INDEX idx_voters_gharana ON voters(gharana_no);
CREATE INDEX idx_voters_silsala ON voters(silsala_no);
CREATE INDEX idx_voters_block ON voters(block_code);

-- 3. Search Telemetry Queue (Offline Pending Sync)
CREATE TABLE search_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    query_type TEXT,
    search_term TEXT,
    results_count INTEGER,
    searched_at TEXT,
    is_synced INTEGER DEFAULT 0
);
```

---

## 🧪 7. Quick Testing Commands (cURL & PowerShell)

### cURL Login Test:
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/login" \
     -H "Content-Type: application/json" \
     -d '{
       "email": "usman@gmail.com",
       "password": "password",
       "device_uid": "test-device-uuid-12345",
       "device_name": "Developer Machine",
       "platform": "android"
     }'
```

### PowerShell Test:
```powershell
$body = @{
    email = "usman@gmail.com"
    password = "password"
    device_uid = "powershell-uuid-123"
    device_name = "Windows Test Terminal"
    platform = "android"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -Body $body -ContentType "application/json"
```

---
*VoterApp REST API v1 &bull; Documented on September 03, 2026.*
