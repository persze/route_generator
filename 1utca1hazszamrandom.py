#!/usr/bin/env python3
import csv
import random
import sys
import os

INPUT_CSV = "output.csv"       # az előző script eredménye
OUTPUT_CSV = "output_unique.csv"

if not os.path.exists(INPUT_CSV):
    print(f"[ERROR] {INPUT_CSV} nem található!")
    sys.exit(1)

# Beolvasás
with open(INPUT_CSV, "r", encoding="utf-8") as f:
    reader = csv.DictReader(f, delimiter=";")
    data = list(reader)

# Város+utca kulcs alapján csoportosítás
city_street_map = {}
for row in data:
    key = (row["VAROS"], row["UTCA"])
    if key not in city_street_map:
        city_street_map[key] = []
    city_street_map[key].append(row)

# Minden város-utcából véletlenszerűen 1 sor
unique_rows = []
for key, rows in city_street_map.items():
    chosen = random.choice(rows)
    unique_rows.append(chosen)

# Mentés új CSV-be
with open(OUTPUT_CSV, "w", newline="", encoding="utf-8") as f:
    fieldnames = ["ID","ORSZAG","VAROS","UTCA","HAZSZAM","LAT","LON"]
    writer = csv.DictWriter(f, fieldnames=fieldnames, delimiter=";")
    writer.writeheader()
    for row in unique_rows:
        writer.writerow(row)

print(f"CSV elkészült: {OUTPUT_CSV} ({len(unique_rows)} rekord)")
