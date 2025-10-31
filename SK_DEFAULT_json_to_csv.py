#!/usr/bin/env python3
import json
import hashlib
import csv
import os
import sys

INPUT_FILE = "database.json"
OUTPUT_FILE = "output.csv"
DEFAULT_COUNTRY = "SK"  # ha nincs ország mező

def generate_id(lat, lon):
    """Deterministic ID a koordináták alapján"""
    key = f"{lat:.6f}|{lon:.6f}"
    return hashlib.sha1(key.encode("utf-8")).hexdigest()

# JSON betöltése
if not os.path.exists(INPUT_FILE):
    print(f"[ERROR] {INPUT_FILE} nem található!")
    sys.exit(1)

with open(INPUT_FILE, "r", encoding="utf-8") as f:
    data = json.load(f)

# Overpass style: elements tömb
elements = data.get("elements", data if isinstance(data, list) else [])

seen_coords = set()
rows = []

for el in elements:
    tags = el.get("tags", {})
    lat = el.get("lat")
    lon = el.get("lon")
    if lat is None or lon is None:
        continue

    coord_key = (round(lat,6), round(lon,6))
    if coord_key in seen_coords:
        continue  # ne legyen duplikátum
    seen_coords.add(coord_key)

    country = tags.get("addr:country", DEFAULT_COUNTRY)
    city = tags.get("addr:city", "")
    street = tags.get("addr:street", "")
    housenumber = tags.get("addr:housenumber", "")

    # Ha nincs utcanév, használjuk a város nevét
    if not street:
        street = city

    row_id = generate_id(lat, lon)
    rows.append({
        "ID": row_id,
        "ORSZAG": country,
        "VAROS": city,
        "UTCA": street,
        "HAZSZAM": housenumber,
        "LAT": f"{lat:.6f}",
        "LON": f"{lon:.6f}"
    })

# CSV írása
with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as csvfile:
    fieldnames = ["ID","ORSZAG","VAROS","UTCA","HAZSZAM","LAT","LON"]
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames, delimiter=";")
    writer.writeheader()
    for r in rows:
        writer.writerow(r)

print(f"CSV elkészült: {OUTPUT_FILE} ({len(rows)} rekord)")
