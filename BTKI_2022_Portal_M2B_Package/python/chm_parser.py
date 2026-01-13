#!/usr/bin/env python3
"""
CHM Parser untuk BTKI 2022
Extract HTML content dari CHM file tanpa library eksternal
"""

import struct
import os
import sys

class SimpleCHMParser:
    """Simple CHM parser yang membaca raw binary"""
    
    def __init__(self, chm_path):
        self.chm_path = chm_path
        self.file_list = []
        
    def extract_info(self):
        """Extract basic info dari CHM file"""
        try:
            with open(self.chm_path, 'rb') as f:
                # Read header
                header = f.read(4)
                if header != b'ITSF':
                    print("❌ Not a valid CHM file")
                    return False
                
                # Read version
                version = struct.unpack('<I', f.read(4))[0]
                print(f"✅ CHM File detected")
                print(f"   Version: {version}")
                
                # Get file size
                file_size = os.path.getsize(self.chm_path)
                print(f"   Size: {file_size / 1024 / 1024:.2f} MB")
                
                return True
                
        except Exception as e:
            print(f"❌ Error reading CHM: {e}")
            return False
    
    def search_html_content(self):
        """Search for HTML content patterns in CHM"""
        html_patterns = []
        
        try:
            with open(self.chm_path, 'rb') as f:
                content = f.read()
                
                # Search for HTML markers
                html_starts = []
                pos = 0
                while True:
                    pos = content.find(b'<html', pos)
                    if pos == -1:
                        break
                    html_starts.append(pos)
                    pos += 1
                
                print(f"\n📄 Found {len(html_starts)} HTML sections")
                
                # Extract sample HTML
                if html_starts:
                    for i, start in enumerate(html_starts[:5]):  # First 5 samples
                        # Try to find end tag
                        end = content.find(b'</html>', start)
                        if end != -1:
                            html_content = content[start:end+7]
                            # Try to decode
                            try:
                                decoded = html_content.decode('utf-8', errors='ignore')
                                if len(decoded) > 100:
                                    print(f"\n--- HTML Section {i+1} (first 200 chars) ---")
                                    print(decoded[:200])
                            except:
                                pass
                
                return html_starts
                
        except Exception as e:
            print(f"❌ Error searching HTML: {e}")
            return []

if __name__ == "__main__":
    parser = SimpleCHMParser('/home/user/btki_project/E-BTKI_2022.chm')
    
    print("=" * 60)
    print("CHM File Analysis")
    print("=" * 60)
    
    if parser.extract_info():
        parser.search_html_content()
    
    print("\n" + "=" * 60)
    print("\n💡 CHM file terdeteksi dan berisi HTML content")
    print("   Untuk full extraction, diperlukan tools eksternal")
    print("   Alternatif: Manual extraction menggunakan Windows atau")
    print("   menggunakan data Excel yang sudah tersedia")
