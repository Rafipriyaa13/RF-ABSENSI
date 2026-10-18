-- database/schema.sql
-- Sistem Monitoring Absensi Pegawai Richeese Factory

DROP TABLE IF EXISTS absensi   CASCADE;
DROP TABLE IF EXISTS izin_cuti CASCADE;
DROP TABLE IF EXISTS jadwal    CASCADE;
DROP TABLE IF EXISTS shift     CASCADE;
DROP TABLE IF EXISTS users     CASCADE;

CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255),          -- NULL = belum registrasi
    role          VARCHAR(10)  NOT NULL CHECK (role IN ('manager', 'pegawai')),
    status        VARCHAR(10)  NOT NULL DEFAULT 'aktif'
                  CHECK (status IN ('aktif', 'nonaktif'))
);

-- 2. SHIFT (master data)
CREATE TABLE shift (
    id          SERIAL PRIMARY KEY,
    nama_shift  VARCHAR(30) NOT NULL UNIQUE,
    jam_mulai   TIME NOT NULL,
    jam_selesai TIME NOT NULL
);

-- 3. JADWAL
CREATE TABLE jadwal (
    id         SERIAL PRIMARY KEY,
    user_id    INT  NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    shift_id   INT  NOT NULL REFERENCES shift(id) ON DELETE RESTRICT,
    created_by INT  NOT NULL REFERENCES users(id),
    tanggal    DATE NOT NULL,
    status     VARCHAR(10) NOT NULL DEFAULT 'terjadwal'
               CHECK (status IN ('terjadwal', 'libur')),
    UNIQUE (user_id, tanggal)          -- satu pegawai satu shift per hari
);

-- 4. IZIN_CUTI
CREATE TABLE izin_cuti (
    id              SERIAL PRIMARY KEY,
    user_id         INT  NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    approved_by     INT  REFERENCES users(id),     -- NULL selama masih menunggu
    jenis           VARCHAR(10) NOT NULL CHECK (jenis IN ('izin', 'sakit', 'cuti')),
    tanggal_mulai   DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status_approval VARCHAR(10) NOT NULL DEFAULT 'menunggu'
                    CHECK (status_approval IN ('menunggu', 'disetujui', 'ditolak')),
    CHECK (tanggal_selesai >= tanggal_mulai)
);

-- 5. ABSENSI
CREATE TABLE absensi (
    id               SERIAL PRIMARY KEY,
    jadwal_id        INT NOT NULL REFERENCES jadwal(id) ON DELETE CASCADE,
    user_id          INT NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    check_in         TIMESTAMP,
    check_out        TIMESTAMP,
    status_kehadiran VARCHAR(15) NOT NULL
                     CHECK (status_kehadiran IN ('tepat_waktu', 'terlambat')),
    bukti_foto       VARCHAR(255),UNIQUE (jadwal_id),             
    CHECK (check_out IS NULL OR check_out >= check_in)
);

CREATE INDEX idx_jadwal_tanggal   ON jadwal (tanggal);
CREATE INDEX idx_jadwal_user      ON jadwal (user_id, tanggal);
CREATE INDEX idx_izin_user_status ON izin_cuti (user_id, status_approval);
CREATE INDEX idx_absensi_user     ON absensi (user_id);