#!/usr/bin/env python3
"""
BTKI 2022 Excel Importer to MySQL
Untuk Portal M2B - Laravel + MySQL
Author: AI Assistant
Date: 2026-01-01

Usage:
    python btki_importer.py --excel "E-BTKI 2022.xlsx" --db-host localhost --db-name portal_m2b
"""

import pandas as pd
import mysql.connector
from mysql.connector import Error
import re
import argparse
from datetime import datetime
import sys
from typing import Optional, Dict, List, Tuple
import logging

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('btki_import.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)


class BTKIImporter:
    """Main importer class untuk BTKI 2022"""
    
    def __init__(self, db_config: Dict[str, str]):
        self.db_config = db_config
        self.conn = None
        self.cursor = None
        self.batch_id = datetime.now().strftime('%Y%m%d_%H%M%S')
        self.stats = {
            'sections': 0,
            'chapters': 0,
            'hs_codes': 0,
            'skipped': 0,
            'errors': 0
        }
        
    def connect(self) -> bool:
        """Connect ke MySQL database"""
        try:
            self.conn = mysql.connector.connect(
                host=self.db_config['host'],
                user=self.db_config['user'],
                password=self.db_config['password'],
                database=self.db_config['database'],
                charset='utf8mb4',
                use_unicode=True
            )
            self.cursor = self.conn.cursor()
            logger.info("✅ Connected to MySQL database")
            return True
        except Error as e:
            logger.error(f"❌ Database connection failed: {e}")
            return False
    
    def close(self):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
        if self.conn:
            self.conn.close()
        logger.info("Database connection closed")
    
    def normalize_hs_code(self, hs_code: str) -> Optional[str]:
        """
        Normalize HS Code format
        Input: '0101.21.00', '01.01', '010121', dll
        Output: '01.01.21.00' (standard format)
        """
        if pd.isna(hs_code) or not hs_code:
            return None
        
        # Convert to string and clean
        hs_str = str(hs_code).strip().replace(' ', '')
        
        # Remove all dots
        digits_only = re.sub(r'[^0-9]', '', hs_str)
        
        if not digits_only:
            return None
        
        # Pad with zeros if needed
        digits_only = digits_only.ljust(10, '0')[:10]
        
        # Format based on length
        if len(digits_only) >= 10:
            return f"{digits_only[:2]}.{digits_only[2:4]}.{digits_only[4:6]}.{digits_only[6:8]}"
        elif len(digits_only) >= 8:
            return f"{digits_only[:2]}.{digits_only[2:4]}.{digits_only[4:6]}.{digits_only[6:8]}"
        elif len(digits_only) >= 6:
            return f"{digits_only[:2]}.{digits_only[2:4]}.{digits_only[4:6]}"
        elif len(digits_only) >= 4:
            return f"{digits_only[:2]}.{digits_only[2:4]}"
        elif len(digits_only) >= 2:
            return f"{digits_only[:2]}"
        
        return None
    
    def get_hs_level(self, hs_code: str) -> int:
        """Determine HS Code level"""
        if not hs_code:
            return 0
        
        digits = hs_code.replace('.', '')
        length = len(digits)
        
        if length == 2:
            return 2  # Chapter
        elif length == 4:
            return 4  # Heading
        elif length == 6:
            return 6  # Subheading
        elif length == 8:
            return 8  # Detail
        elif length >= 10:
            return 10  # Subdetail
        
        return 0
    
    def get_parent_code(self, hs_code: str) -> Optional[str]:
        """Get parent HS Code"""
        if not hs_code:
            return None
        
        level = self.get_hs_level(hs_code)
        digits = hs_code.replace('.', '')
        
        if level == 2:
            return None  # Chapter has no parent
        elif level == 4:
            return f"{digits[:2]}"
        elif level == 6:
            return f"{digits[:2]}.{digits[2:4]}"
        elif level == 8:
            return f"{digits[:2]}.{digits[2:4]}.{digits[4:6]}"
        elif level == 10:
            return f"{digits[:2]}.{digits[2:4]}.{digits[4:6]}.{digits[6:8]}"
        
        return None
    
    def detect_section(self, text: str) -> Optional[Dict]:
        """Detect Section dari text"""
        if pd.isna(text):
            return None
        
        text_str = str(text).strip()
        
        # Pattern untuk Section: "Bagian I" atau "Section I"
        patterns = [
            r'(?:Bagian|Section)\s+([IVX]+)',
            r'^([IVX]+)\s*-?\s*(.+)',
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text_str, re.IGNORECASE)
            if match:
                return {
                    'section_number': match.group(1),
                    'title': match.group(2) if len(match.groups()) > 1 else ''
                }
        
        return None
    
    def detect_chapter(self, text: str) -> Optional[Dict]:
        """Detect Chapter dari text"""
        if pd.isna(text):
            return None
        
        text_str = str(text).strip()
        
        # Pattern untuk Chapter: "Bab 1" atau "Chapter 1"
        patterns = [
            r'(?:Bab|Chapter)\s+(\d+)',
            r'^(\d{1,2})\s*-?\s*(.+)',
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text_str, re.IGNORECASE)
            if match:
                chapter_num = match.group(1).zfill(2)
                return {
                    'chapter_number': chapter_num,
                    'title': match.group(2) if len(match.groups()) > 1 else ''
                }
        
        return None
    
    def load_excel(self, excel_path: str) -> pd.DataFrame:
        """Load Excel file"""
        logger.info(f"📊 Loading Excel: {excel_path}")
        
        try:
            # Read with multiple sheet names to find the right one
            excel_file = pd.ExcelFile(excel_path)
            logger.info(f"Available sheets: {excel_file.sheet_names}")
            
            # Try to find main data sheet
            sheet_name = 'Table 1' if 'Table 1' in excel_file.sheet_names else excel_file.sheet_names[0]
            
            df = pd.read_excel(
                excel_path,
                sheet_name=sheet_name,
                dtype=str,  # Read everything as string first
                na_filter=False
            )
            
            logger.info(f"✅ Loaded {len(df)} rows from sheet '{sheet_name}'")
            logger.info(f"Columns: {list(df.columns)[:5]}...")  # Show first 5 columns
            
            return df
            
        except Exception as e:
            logger.error(f"❌ Failed to load Excel: {e}")
            raise
    
    def clean_description(self, text: str) -> str:
        """Clean description text"""
        if pd.isna(text) or not text:
            return ''
        
        # Remove extra whitespace
        cleaned = ' '.join(str(text).split())
        
        # Remove leading/trailing dashes and dots
        cleaned = cleaned.strip(' -.:')
        
        return cleaned
    
    def import_sections(self, df: pd.DataFrame):
        """Import Sections dari DataFrame"""
        logger.info("📥 Importing Sections...")
        
        current_section = None
        
        for idx, row in df.iterrows():
            try:
                # Check first few columns for section markers
                for col in df.columns[:3]:
                    cell_value = str(row[col])
                    
                    section_info = self.detect_section(cell_value)
                    if section_info:
                        # Get bilingual titles if available
                        title_id = section_info.get('title', '')
                        title_en = ''
                        
                        # Try to find English title in next columns
                        if idx + 1 < len(df):
                            next_row = df.iloc[idx + 1]
                            for col2 in df.columns[:3]:
                                if 'section' in str(next_row[col2]).lower():
                                    title_en = self.clean_description(next_row[col2])
                                    break
                        
                        # Insert to database
                        sql = """
                        INSERT INTO hs_sections (section_number, title_id, title_en, display_order)
                        VALUES (%s, %s, %s, %s)
                        ON DUPLICATE KEY UPDATE 
                            title_id = VALUES(title_id),
                            title_en = VALUES(title_en)
                        """
                        
                        self.cursor.execute(sql, (
                            section_info['section_number'],
                            title_id,
                            title_en,
                            self.stats['sections'] + 1
                        ))
                        
                        self.stats['sections'] += 1
                        logger.info(f"  ✓ Section {section_info['section_number']}: {title_id[:50]}")
                        break
                        
            except Exception as e:
                logger.warning(f"  ⚠️ Row {idx} section detection error: {e}")
                continue
        
        self.conn.commit()
        logger.info(f"✅ Imported {self.stats['sections']} sections")
    
    def import_chapters(self, df: pd.DataFrame):
        """Import Chapters dari DataFrame"""
        logger.info("📥 Importing Chapters...")
        
        for idx, row in df.iterrows():
            try:
                # Check first column for chapter markers
                first_col = str(row[df.columns[0]])
                
                chapter_info = self.detect_chapter(first_col)
                if chapter_info:
                    # Get descriptions from subsequent columns
                    title_id = ''
                    title_en = ''
                    
                    if len(df.columns) > 1:
                        title_id = self.clean_description(row[df.columns[1]])
                    if len(df.columns) > 2:
                        title_en = self.clean_description(row[df.columns[2]])
                    
                    # Insert to database
                    sql = """
                    INSERT INTO hs_chapters (chapter_number, title_id, title_en, display_order)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE 
                        title_id = VALUES(title_id),
                        title_en = VALUES(title_en)
                    """
                    
                    self.cursor.execute(sql, (
                        chapter_info['chapter_number'],
                        title_id,
                        title_en,
                        self.stats['chapters'] + 1
                    ))
                    
                    self.stats['chapters'] += 1
                    logger.info(f"  ✓ Chapter {chapter_info['chapter_number']}: {title_id[:50]}")
                        
            except Exception as e:
                logger.warning(f"  ⚠️ Row {idx} chapter detection error: {e}")
                continue
        
        self.conn.commit()
        logger.info(f"✅ Imported {self.stats['chapters']} chapters")
    
    def import_hs_codes(self, df: pd.DataFrame):
        """Import HS Codes - Main data import (17.000+ rows)"""
        logger.info("📥 Importing HS Codes...")
        logger.info("This may take several minutes for 17,000+ rows...")
        
        batch = []
        batch_size = 500
        
        # Identify relevant columns
        hs_col = None
        desc_id_col = None
        desc_en_col = None
        
        # Auto-detect columns
        for col in df.columns:
            col_lower = col.lower()
            if 'hs' in col_lower or 'tarif' in col_lower or 'code' in col_lower:
                if not hs_col:
                    hs_col = col
            elif 'uraian' in col_lower or 'barang' in col_lower:
                if not desc_id_col:
                    desc_id_col = col
            elif 'description' in col_lower or 'goods' in col_lower:
                if not desc_en_col:
                    desc_en_col = col
        
        logger.info(f"Detected columns - HS: {hs_col}, Desc ID: {desc_id_col}, Desc EN: {desc_en_col}")
        
        for idx, row in df.iterrows():
            try:
                # Get HS Code
                hs_raw = row[hs_col] if hs_col else ''
                if not hs_raw or hs_raw == '':
                    continue
                
                # Normalize HS Code
                hs_code = self.normalize_hs_code(hs_raw)
                if not hs_code:
                    self.stats['skipped'] += 1
                    continue
                
                # Get descriptions
                desc_id = self.clean_description(row[desc_id_col] if desc_id_col else '')
                desc_en = self.clean_description(row[desc_en_col] if desc_en_col else '')
                
                # Skip if no description
                if not desc_id and not desc_en:
                    self.stats['skipped'] += 1
                    continue
                
                # Get level and parent
                level = self.get_hs_level(hs_code)
                parent_code = self.get_parent_code(hs_code)
                chapter_number = hs_code.split('.')[0]
                
                # Add to batch
                batch.append((
                    hs_code,
                    level,
                    parent_code,
                    desc_id,
                    desc_en,
                    chapter_number,
                    True,  # is_active
                    '2022-04-01',  # effective_date
                    self.batch_id
                ))
                
                # Execute batch
                if len(batch) >= batch_size:
                    self._execute_batch(batch)
                    batch = []
                    
                    if self.stats['hs_codes'] % 1000 == 0:
                        logger.info(f"  Progress: {self.stats['hs_codes']} HS Codes imported...")
                        
            except Exception as e:
                logger.warning(f"  ⚠️ Row {idx} error: {e}")
                self.stats['errors'] += 1
                continue
        
        # Execute remaining batch
        if batch:
            self._execute_batch(batch)
        
        self.conn.commit()
        logger.info(f"✅ Imported {self.stats['hs_codes']} HS Codes")
        logger.info(f"⚠️ Skipped {self.stats['skipped']} rows")
        logger.info(f"❌ Errors: {self.stats['errors']} rows")
    
    def _execute_batch(self, batch: List[Tuple]):
        """Execute batch insert"""
        try:
            sql = """
            INSERT INTO hs_codes 
            (hs_code, hs_level, parent_code, description_id, description_en, 
             chapter_number, is_active, effective_date, import_batch_id)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE 
                description_id = VALUES(description_id),
                description_en = VALUES(description_en),
                parent_code = VALUES(parent_code)
            """
            
            self.cursor.executemany(sql, batch)
            self.stats['hs_codes'] += len(batch)
            
        except Error as e:
            logger.error(f"Batch insert error: {e}")
            # Try individual inserts
            for record in batch:
                try:
                    self.cursor.execute(sql, record)
                    self.stats['hs_codes'] += 1
                except Error as e2:
                    logger.error(f"Individual insert failed for {record[0]}: {e2}")
                    self.stats['errors'] += 1
    
    def create_import_record(self, file_name: str, status: str = 'completed'):
        """Create import history record"""
        try:
            sql = """
            INSERT INTO hs_import_history 
            (batch_id, file_name, total_rows, imported_rows, skipped_rows, error_rows, 
             status, completed_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
            """
            
            total = sum([self.stats['sections'], self.stats['chapters'], self.stats['hs_codes']])
            
            self.cursor.execute(sql, (
                self.batch_id,
                file_name,
                total + self.stats['skipped'] + self.stats['errors'],
                total,
                self.stats['skipped'],
                self.stats['errors'],
                status
            ))
            
            self.conn.commit()
            logger.info(f"✅ Import history recorded (Batch ID: {self.batch_id})")
            
        except Error as e:
            logger.error(f"Failed to create import record: {e}")
    
    def run(self, excel_path: str):
        """Run complete import process"""
        logger.info("=" * 70)
        logger.info("🚀 BTKI 2022 Import Started")
        logger.info(f"   Batch ID: {self.batch_id}")
        logger.info(f"   File: {excel_path}")
        logger.info("=" * 70)
        
        start_time = datetime.now()
        
        try:
            # 1. Connect to database
            if not self.connect():
                return False
            
            # 2. Load Excel
            df = self.load_excel(excel_path)
            
            # 3. Import Sections
            self.import_sections(df)
            
            # 4. Import Chapters
            self.import_chapters(df)
            
            # 5. Import HS Codes (main data - 17,000+ rows)
            self.import_hs_codes(df)
            
            # 6. Create import record
            self.create_import_record(excel_path, 'completed')
            
            # Calculate duration
            duration = datetime.now() - start_time
            
            logger.info("=" * 70)
            logger.info("✅ IMPORT COMPLETED SUCCESSFULLY!")
            logger.info(f"   Duration: {duration}")
            logger.info(f"   Sections: {self.stats['sections']}")
            logger.info(f"   Chapters: {self.stats['chapters']}")
            logger.info(f"   HS Codes: {self.stats['hs_codes']}")
            logger.info(f"   Skipped: {self.stats['skipped']}")
            logger.info(f"   Errors: {self.stats['errors']}")
            logger.info("=" * 70)
            
            return True
            
        except Exception as e:
            logger.error(f"❌ IMPORT FAILED: {e}")
            logger.exception(e)
            
            # Record failed import
            try:
                self.create_import_record(excel_path, 'failed')
            except:
                pass
            
            return False
            
        finally:
            self.close()


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='BTKI 2022 Excel to MySQL Importer')
    parser.add_argument('--excel', required=True, help='Path to Excel file')
    parser.add_argument('--db-host', default='localhost', help='MySQL host')
    parser.add_argument('--db-user', default='root', help='MySQL user')
    parser.add_argument('--db-password', default='', help='MySQL password')
    parser.add_argument('--db-name', default='portal_m2b', help='Database name')
    
    args = parser.parse_args()
    
    db_config = {
        'host': args.db_host,
        'user': args.db_user,
        'password': args.db_password,
        'database': args.db_name
    }
    
    importer = BTKIImporter(db_config)
    success = importer.run(args.excel)
    
    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
