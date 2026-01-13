# 🚀 SOLUSI LENGKAP: Integrasi HS Code BTKI 2022 ke Portal M2B

## 📋 Overview

Berdasarkan file yang Anda berikan dan screenshot interface ideal, saya akan buatkan solusi lengkap yang mengkolaborasikan semua fitur:

### ✨ Fitur Utama:
1. ✅ **Search & Autocomplete** - Real-time search dengan Livewire
2. ✅ **Hierarchical Display** - Tree view Chapter → Heading → Subheading
3. ✅ **Explanatory Notes** - Link ke catatan penjelasan per HS Code
4. ✅ **General Rules** - Akses ke ketentuan umum interpretasi
5. ✅ **Bilingual** - Support Bahasa Indonesia & English
6. ✅ **Export/Import** - Tools untuk data management
7. ✅ **Responsive UI** - Modern interface dengan Livewire

---

## 🗄️ DATABASE SCHEMA (MySQL)

### Schema Lengkap dengan semua tabel yang dibutuhkan

```sql
-- =====================================================
-- BTKI 2022 Database Schema for Portal M2B
-- =====================================================

-- 1. Table: hs_sections (Bagian/Section)
CREATE TABLE hs_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_number VARCHAR(10) NOT NULL UNIQUE COMMENT 'I, II, III, dst',
    title_id TEXT NOT NULL COMMENT 'Judul Bagian (Indonesia)',
    title_en TEXT NULL COMMENT 'Section Title (English)',
    notes TEXT NULL COMMENT 'Catatan bagian',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: hs_chapters (Bab/Chapter)
CREATE TABLE hs_chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapter_number VARCHAR(2) NOT NULL UNIQUE COMMENT '01, 02, 03, dst',
    title_id TEXT NOT NULL COMMENT 'Judul Bab (Indonesia)',
    title_en TEXT NULL COMMENT 'Chapter Title (English)',
    section_id INT UNSIGNED NULL COMMENT 'Reference ke section',
    notes TEXT NULL COMMENT 'Chapter notes',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES hs_sections(id) ON DELETE SET NULL,
    INDEX idx_chapter (chapter_number),
    INDEX idx_section (section_id),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: hs_codes (Main HS Code Data - 17.000+ rows)
CREATE TABLE hs_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hs_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: XX.XX.XX.XX',
    hs_level TINYINT NOT NULL COMMENT '2=Bab, 4=Pos, 6=Subpos, 8=Detail, 10=Subdetail',
    parent_code VARCHAR(20) NULL COMMENT 'Parent HS Code',
    description_id TEXT NOT NULL COMMENT 'Uraian Barang (Indonesia)',
    description_en TEXT NULL COMMENT 'Description of Goods (English)',
    chapter_number VARCHAR(2) NOT NULL COMMENT 'Reference ke chapter',
    section_number VARCHAR(10) NULL COMMENT 'Reference ke section',
    
    -- Additional fields
    is_active BOOLEAN DEFAULT TRUE,
    effective_date DATE NULL COMMENT 'Tanggal berlaku',
    notes TEXT NULL COMMENT 'Catatan tambahan',
    
    -- Explanatory Notes (dari CHM atau manual)
    has_explanatory_note BOOLEAN DEFAULT FALSE,
    explanatory_note_url VARCHAR(255) NULL COMMENT 'Link ke explanatory note',
    explanatory_note_content LONGTEXT NULL COMMENT 'Full text explanatory note',
    
    -- Metadata
    import_batch_id VARCHAR(50) NULL COMMENT 'Tracking import batch',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_hs_code (hs_code),
    INDEX idx_level (hs_level),
    INDEX idx_parent (parent_code),
    INDEX idx_chapter (chapter_number),
    INDEX idx_section (section_number),
    INDEX idx_active (is_active),
    INDEX idx_has_en (has_explanatory_note),
    
    -- Full-text search index
    FULLTEXT INDEX idx_search_id (description_id),
    FULLTEXT INDEX idx_search_en (description_en),
    FULLTEXT INDEX idx_search_both (description_id, description_en),
    
    -- Foreign keys
    FOREIGN KEY (parent_code) REFERENCES hs_codes(hs_code) ON DELETE SET NULL,
    FOREIGN KEY (chapter_number) REFERENCES hs_chapters(chapter_number) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: hs_general_rules (Ketentuan Umum Interpretasi)
CREATE TABLE hs_general_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT 'Judul aturan',
    content_id LONGTEXT NOT NULL COMMENT 'Konten lengkap (Indonesia)',
    content_en LONGTEXT NULL COMMENT 'Content (English)',
    rule_order INT NOT NULL DEFAULT 1 COMMENT 'Urutan aturan (1-6)',
    version VARCHAR(50) NOT NULL DEFAULT 'BTKI 2022 v1',
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_version (version),
    INDEX idx_order (rule_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: hs_section_notes (Catatan Bagian)
CREATE TABLE hs_section_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    note_text LONGTEXT NOT NULL,
    note_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES hs_sections(id) ON DELETE CASCADE,
    INDEX idx_section (section_id),
    INDEX idx_order (note_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table: hs_chapter_notes (Catatan Bab)
CREATE TABLE hs_chapter_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT UNSIGNED NOT NULL,
    note_text LONGTEXT NOT NULL,
    note_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES hs_chapters(id) ON DELETE CASCADE,
    INDEX idx_chapter (chapter_id),
    INDEX idx_order (note_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: hs_explanatory_notes (Explanatory Notes Detail)
CREATE TABLE hs_explanatory_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hs_code VARCHAR(20) NOT NULL,
    note_title VARCHAR(255) NULL,
    note_content LONGTEXT NOT NULL,
    note_type ENUM('general', 'subheading', 'exclusion', 'example') DEFAULT 'general',
    language VARCHAR(5) DEFAULT 'id' COMMENT 'id atau en',
    source VARCHAR(100) NULL COMMENT 'Source: CHM, Manual, WCO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hs_code) REFERENCES hs_codes(hs_code) ON DELETE CASCADE,
    INDEX idx_hs_code (hs_code),
    INDEX idx_type (note_type),
    INDEX idx_language (language),
    FULLTEXT INDEX idx_content (note_content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table: hs_search_logs (Search Analytics)
CREATE TABLE hs_search_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,
    result_count INT NOT NULL DEFAULT 0,
    selected_hs_code VARCHAR(20) NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'Reference ke users table',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    search_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query (search_query),
    INDEX idx_date (search_date),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table: hs_favorites (User Favorites)
CREATE TABLE hs_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    hs_code VARCHAR(20) NOT NULL,
    notes TEXT NULL COMMENT 'User personal notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_code (user_id, hs_code),
    FOREIGN KEY (hs_code) REFERENCES hs_codes(hs_code) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_code (hs_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table: hs_import_history (Import Tracking)
CREATE TABLE hs_import_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(50) NOT NULL UNIQUE,
    file_name VARCHAR(255) NOT NULL,
    total_rows INT NOT NULL DEFAULT 0,
    imported_rows INT NOT NULL DEFAULT 0,
    skipped_rows INT NOT NULL DEFAULT 0,
    error_rows INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_log LONGTEXT NULL,
    imported_by BIGINT UNSIGNED NULL COMMENT 'User who imported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch (batch_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📊 SAMPLE DATA untuk Testing

```sql
-- Insert General Rules (6 Ketentuan Utama)
INSERT INTO hs_general_rules (title, content_id, content_en, rule_order, version, effective_date) VALUES
('Ketentuan 1', 'Judul dari Bagian, Bab dan Sub-bab dimaksudkan hanya untuk mempermudah referensi saja...', 
 'The titles of Sections, Chapters and sub-Chapters are provided for ease of reference only...', 
 1, 'BTKI 2022 v1', '2022-04-01'),
 
('Ketentuan 2', 'Setiap referensi untuk suatu barang dalam suatu pos harus dianggap meliputi juga referensi...', 
 'Any reference to a heading to an article shall be taken to include a reference...', 
 2, 'BTKI 2022 v1', '2022-04-01'),
 
('Ketentuan 3', 'Apabila dengan menerapkan Ketentuan 2 (b) atau untuk berbagai alasan lain...', 
 'When by application of Rule 2 (b) or for any other reason...', 
 3, 'BTKI 2022 v1', '2022-04-01'),
 
('Ketentuan 4', 'Barang yang tidak dapat diklasifikasikan berdasarkan Ketentuan di atas...', 
 'Goods which cannot be classified in accordance with the above Rules...', 
 4, 'BTKI 2022 v1', '2022-04-01'),
 
('Ketentuan 5', 'Sebagai tambahan aturan di atas, Ketentuan berikut ini harus diberlakukan...', 
 'In addition to the foregoing provisions, the following Rules shall apply...', 
 5, 'BTKI 2022 v1', '2022-04-01'),
 
('Ketentuan 6', 'Untuk keperluan hukum, klasifikasi barang dalam subpos dari suatu pos...', 
 'For legal purposes, the classification of goods in the subheadings of a heading...', 
 6, 'BTKI 2022 v1', '2022-04-01');

-- Insert Sample Sections
INSERT INTO hs_sections (section_number, title_id, title_en, display_order) VALUES
('I', 'Binatang hidup; produk hewani', 'Live animals; animal products', 1),
('II', 'Produk nabati', 'Vegetable products', 2),
('III', 'Lemak dan minyak hewani atau nabati', 'Animal or vegetable fats and oils', 3),
('IV', 'Produk industri makanan', 'Prepared foodstuffs', 4),
('V', 'Produk mineral', 'Mineral products', 5);

-- Insert Sample Chapters
INSERT INTO hs_chapters (chapter_number, title_id, title_en, section_id, display_order) VALUES
('01', 'Binatang hidup', 'Live animals', 1, 1),
('02', 'Daging dan sisa daging yang dapat dimakan', 'Meat and edible meat offal', 1, 2),
('03', 'Ikan dan krustasea, moluska serta invertebrata air lainnya', 'Fish and crustaceans, molluscs and other aquatic invertebrates', 1, 3),
('04', 'Produk susu; telur unggas; madu alam', 'Dairy produce; birds eggs; natural honey', 1, 4),
('05', 'Produk hewani, tidak dirinci atau termasuk dalam pos lain', 'Products of animal origin, not elsewhere specified or included', 1, 5);

-- Insert Sample HS Codes (hierarki lengkap)
-- Chapter level
INSERT INTO hs_codes (hs_code, hs_level, parent_code, description_id, description_en, chapter_number, is_active) VALUES
('01', 2, NULL, 'Binatang hidup', 'Live animals', '01', TRUE);

-- Heading level
INSERT INTO hs_codes (hs_code, hs_level, parent_code, description_id, description_en, chapter_number, is_active) VALUES
('01.01', 4, '01', 'Kuda, keledai, bagal dan hinnie, hidup', 'Live horses, asses, mules and hinnies', '01', TRUE),
('01.02', 4, '01', 'Binatang hidup jenis lembu', 'Live bovine animals', '01', TRUE);

-- Subheading level
INSERT INTO hs_codes (hs_code, hs_level, parent_code, description_id, description_en, chapter_number, is_active) VALUES
('0101.21', 6, '01.01', 'Kuda murni untuk pembiakan', 'Pure-bred breeding animals', '01', TRUE),
('0101.29', 6, '01.01', 'Lain-lain', 'Other', '01', TRUE),
('0101.30', 6, '01.01', 'Keledai', 'Asses', '01', TRUE);

-- Detail level
INSERT INTO hs_codes (hs_code, hs_level, parent_code, description_id, description_en, chapter_number, has_explanatory_note, is_active) VALUES
('0101.21.00', 8, '0101.21', 'Kuda murni untuk pembiakan', 'Pure-bred breeding horses', '01', FALSE, TRUE),
('0101.29.00', 8, '0101.29', 'Kuda lainnya', 'Other horses', '01', FALSE, TRUE),
('0101.30.10', 8, '0101.30', 'Keledai bibit', 'Breeding asses', '01', FALSE, TRUE),
('0101.30.90', 8, '0101.30', 'Keledai lainnya', 'Other asses', '01', FALSE, TRUE);

-- Insert Sample Explanatory Notes
INSERT INTO hs_explanatory_notes (hs_code, note_title, note_content, note_type, language) VALUES
('01.01', 'Catatan Pos 01.01', 
'Pos ini mencakup semua kuda hidup termasuk kuda balap, kuda beban, kuda poni, dll. 
Kuda yang telah dipotong atau dalam bentuk karkas tidak termasuk dalam pos ini (lihat Bab 02).', 
'general', 'id'),

('01.01', 'Heading 01.01 Notes', 
'This heading covers all live horses including race horses, work horses, ponies, etc. 
Horses that have been slaughtered or in carcass form are not included (see Chapter 02).', 
'general', 'en');
```

---

## 🔥 Lanjut ke Part 2: Laravel Implementation
File ini terlalu panjang, akan saya lanjutkan dengan file terpisah untuk:
1. Laravel Models & Migrations
2. Python Import Script
3. Livewire Components
4. API Controllers
5. Frontend Views

