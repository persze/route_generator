#!/usr/bin/env python3
import json
import os
import sys

INPUT_FILE = "database.json"
OUTPUT_FILE = "database_clean.json"

if not os.path.exists(INPUT_FILE):
    print(f"[ERROR] {INPUT_FILE} nem található!")
    sys.exit(1)

with open(INPUT_FILE, "r", encoding="utf-8") as f:
    data = json.load(f)

# Overpass style: elements tömb
elements = data.get("elements", data if isinstance(data, list) else [])

cleaned_elements = []
for el in elements:
    tags = el.get("tags", {})
    city = tags.get("addr:city", "").strip()
    if city:  # csak ha van város
        cleaned_elements.append(el)

# Mentés új JSON-be
output_data = {"elements": cleaned_elements}

with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
    json.dump(output_data, f, ensure_ascii=False, indent=2)

print(f"Tisztított JSON elkészült: {OUTPUT_FILE} ({len(cleaned_elements)} rekord)")
