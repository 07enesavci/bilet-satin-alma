PRAGMA foreign_keys = ON;  -- İlişkili tablolarda silme/güncelleme kısıtlarını etkinleştirir

-- /////////////// Otobüs Firmalarının Tablosu ///////////////
CREATE TABLE IF NOT EXISTS Bus_Company (
  id TEXT PRIMARY KEY,                          -- Her firmaya özel UUID
  name TEXT UNIQUE NOT NULL,                    -- Firma adı
  logo_path TEXT,                               
  created_at TEXT NOT NULL DEFAULT (datetime('now'))  -- Oluşturulma tarihi
);

-- //////////////// Kullanıcıların Tablosu ////////////////
CREATE TABLE IF NOT EXISTS User (
  id TEXT PRIMARY KEY,                          -- Kullanıcı  ID
  full_name TEXT NOT NULL,                      -- Ad Soyad
  email TEXT UNIQUE NOT NULL,                   -- E-posta adresi 
  role TEXT NOT NULL CHECK(role IN ('user','company','admin')),  -- Kullanıcı rolü
  password TEXT NOT NULL,                       -- Şifre hashli şekilde 
  company_id TEXT REFERENCES Bus_Company(id) ON DELETE SET NULL, -- Firma admini ise bağlı olduğu firma
  balance REAL NOT NULL DEFAULT 2000,            -- Kullanıcının bakiyesi 
  created_at TEXT NOT NULL DEFAULT (datetime('now'))  -- Oluşturulma tarihi
);

-- /////////////// Seferlerin Tablosu ///////////////
CREATE TABLE IF NOT EXISTS Trips (
  id TEXT PRIMARY KEY,                          -- Sefer ID
  company_id TEXT NOT NULL REFERENCES Bus_Company(id) ON DELETE CASCADE, -- Seferi düzenleyen firma
  destination_city TEXT NOT NULL,               -- Varış şehri
  departure_city TEXT NOT NULL,                 -- Kalkış şehri
  departure_time TEXT NOT NULL,                 -- Kalkış zamanı
  arrival_time TEXT NOT NULL,                   -- Varış zamanı
  price REAL NOT NULL,                          -- Bilet fiyatı
  capacity INTEGER NOT NULL CHECK(capacity > 0),-- Toplam koltuk sayısı
  created_date TEXT NOT NULL DEFAULT (datetime('now')) -- Seferin oluşturulma tarihi
);

-- /////////////// Bilet Tablosu ///////////////
CREATE TABLE IF NOT EXISTS Tickets (
  id TEXT PRIMARY KEY,                          -- Bilet ID
  trip_id TEXT NOT NULL REFERENCES Trips(id) ON DELETE CASCADE, -- Hangi sefere ait
  user_id TEXT NOT NULL REFERENCES User(id) ON DELETE CASCADE,  -- Hangi kullanıcıya ait
  status TEXT NOT NULL CHECK(status IN ('active','canceled','expired')) DEFAULT 'active', -- Bilet durumu
  total_price REAL NOT NULL,                    -- Toplam fiyat
  created_at TEXT NOT NULL DEFAULT (datetime('now'))  -- Bilet oluşturulma tarihi
);

-- ////////////// Satın Alınan Koltukların Tablosu //////////////
CREATE TABLE IF NOT EXISTS Booked_Seats (
  id TEXT PRIMARY KEY,                          -- ID
  ticket_id TEXT NOT NULL REFERENCES Tickets(id) ON DELETE CASCADE, -- Hangi bilete ait
  seat_number INTEGER NOT NULL CHECK(seat_number > 0),  -- Koltuk numarası
  created_at TEXT NOT NULL DEFAULT (datetime('now'))    -- Kayıt tarihi
);

-- ////////////// Kupon Tablosu //////////////
CREATE TABLE IF NOT EXISTS Coupons (
  id TEXT PRIMARY KEY,                          -- Kupon ID
  code TEXT NOT NULL,                           -- Kupon kodu 
  discount REAL NOT NULL,                       -- İndirim oranı
  company_id TEXT REFERENCES Bus_Company(id) ON DELETE CASCADE, -- Firma özelinde ya da global kupon
  usage_limit INTEGER NOT NULL DEFAULT 1,       -- Kuponun maksimum kullanım sayısı
  expire_date TEXT NOT NULL,                    -- Son geçerlilik tarihi (YYYY-MM-DD)
  created_at TEXT NOT NULL DEFAULT (datetime('now')), -- Oluşturulma tarihi
  UNIQUE(code, company_id)                      -- Aynı firma içinde aynı kod tekrar edemez
);

-- ////////////// Kullanıcı Kuponları Tablosu //////////////
CREATE TABLE IF NOT EXISTS User_Coupons (
  id TEXT PRIMARY KEY,                          -- ID
  coupon_id TEXT NOT NULL REFERENCES Coupons(id) ON DELETE CASCADE, -- Hangi kupon
  user_id TEXT NOT NULL REFERENCES User(id) ON DELETE CASCADE,      -- Hangi kullanıcı
  created_at TEXT NOT NULL DEFAULT (datetime('now'))                -- Kullanım tarihi
);
