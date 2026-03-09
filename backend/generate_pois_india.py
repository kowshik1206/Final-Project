#!/usr/bin/env python3
"""
generate_pois_india.py
Generates SQL INSERTs for fuel/ev/cng POIs along major Indian highways at ~10km spacing.
"""

import requests, time, math, argparse, json, sys, os
from shapely.geometry import LineString, Point
from shapely.ops import substring
from pyproj import Geod
from datetime import datetime

# CONFIG
OVERPASS_URL = "https://overpass-api.de/api/interpreter"
SEGMENT_METERS = 10000  # 10 km
SEARCH_RADIUS_METERS = 5000  # 5 km search radius (increased to ensure coverage)
CATEGORIES = {
    "fuel": {
        "overpass_tag": '"amenity"="fuel"',
        "pref_brands": ['IndianOil','HP','Bharat Petroleum','BPCL','IOCL','Shell','Reliance']
    },
    "ev": {
        "overpass_tag": '"amenity"="charging_station"',
        "pref_brands": ['Tata Power','Ather','Fortum','ChargeGrid','Bharrier','ZEON']
    },
    "cng": {
        "overpass_tag": '"fuel:cng"="yes"', 
        "pref_brands": ['IGL','Indraprastha Gas','Mahanagar Gas','GAIL']
    },
    "restaurant": {
        "overpass_tag": '"amenity"="restaurant"',
        "pref_brands": ['McDonalds','KFC','Dominos','Subway','Burger King','Haldiram','Bikaner']
    },
    "hotel": {
        "overpass_tag": '"tourism"="hotel"',
        "pref_brands": ['Taj','Oberoi','Marriott','Hyatt','Radisson','Ginger','OYO']
    },
    "hospital": {
        "overpass_tag": '"amenity"="hospital"',
        "pref_brands": ['Apollo','Fortis','Max','Manipal','Narayana','Medanta']
    },
    "police": {
        "overpass_tag": '"amenity"="police"',
        "pref_brands": []
    },
    "tourism": {
        "overpass_tag": '"tourism"~"attraction|museum|viewpoint"',
        "pref_brands": []
    }
}
HIGHWAY_NAMES = [
    # Top Major Highways (North-South, East-West Corridors)
    "NH 44", "NH 27", "NH 48", "NH 52", "NH 30", 
    "NH 6", "NH 53", "NH 16", "NH 66", "NH 19",
    "NH 34", "NH 10", "NH 5",  "NH 1",  "NH 2",
    # Expressways
    "Mumbai Pune Expressway",
    "Yamuna Expressway",
    "Agra Lucknow Expressway",
    "Eastern Peripheral Expressway",
    "Delhi Meerut Expressway",
    "Samruddhi Mahamarg",
    "Purvanchal Expressway",
    "Bundelkhand Expressway",
    # Regional Connectors
    "NH 4", "NH 7", "NH 8", "NH 9", "NH 12", "NH 13", "NH 15", "NH 17",
    "NH 24", "NH 31", "NH 33", "NH 47", "NH 55", "NH 65", "NH 75"
]

geod = Geod(ellps="WGS84")

def haversine_m(a,b):
    lon1,lat1 = a[1],a[0]
    lon2,lat2 = b[1],b[0]
    az12,az21,d = geod.inv(lon1, lat1, lon2, lat2)
    return d

def sample_line_by_distance(coords, interval_m):
    line = LineString([(c[1], c[0]) for c in coords])
    total_length = 0.0
    for i in range(len(coords)-1):
        total_length += haversine_m(coords[i], coords[i+1])
    if total_length < interval_m:
        return [(coords[0][0], coords[0][1])]
    points = []
    dist_along = 0.0
    while dist_along <= total_length:
        frac = dist_along / total_length
        pt = line.interpolate(frac, normalized=True)
        points.append((pt.y, pt.x))
        dist_along += interval_m
    return points

def overpass_query_around(lat, lon, radius, tag_expr):
    tag_expr = tag_expr.strip()
    q = f"""
    [out:json][timeout:60];
    (
      node[{tag_expr}](around:{radius},{lat},{lon});
      way[{tag_expr}](around:{radius},{lat},{lon});
    );
    out center meta;
    """
    try:
        r = requests.post(OVERPASS_URL, data={'data': q})
        if r.status_code != 200:
            print(f"Overpass error {r.status_code}", file=sys.stderr)
            return None
        return r.json()
    except Exception as e:
        print(f"Overpass Exception: {e}", file=sys.stderr)
        return None

def pick_best_poi(osm_elements, center_lat, center_lon, category):
    if not osm_elements: return None
    best = None
    best_score = -1e9
    for el in osm_elements.get('elements', []):
        tags = el.get('tags', {})
        if el.get('type') == 'node':
            lat = el.get('lat'); lon = el.get('lon')
            external_id = f"node/{el['id']}"
        else:
            c = el.get('center') or el.get('bounds')
            if not c: continue
            lat = c.get('lat'); lon = c.get('lon')
            external_id = f"{el.get('type')}/{el['id']}"
        
        dist = haversine_m((center_lat, center_lon), (lat, lon))
        score = max(0, 2000 - dist) / 2000.0
        
        brand = tags.get('brand') or tags.get('operator') or tags.get('name')
        pref = CATEGORIES[category]['pref_brands']
        if brand:
            for p in pref:
                if p.lower() in brand.lower():
                    score += 0.5
                    break
        if category == "fuel" and tags.get('amenity') == 'fuel': score += 0.2
        if category == "ev" and tags.get('amenity') == 'charging_station': score += 0.25
        if category == "restaurant" and tags.get('amenity') == 'restaurant': score += 0.2
        if category == "hotel" and tags.get('tourism') == 'hotel': score += 0.2
        if category == "hospital" and tags.get('amenity') == 'hospital': score += 0.3
        if not tags.get('name'): score -= 0.1
        
        if score > best_score:
            best_score = score
            best = {
                'lat': lat, 'lon': lon, 'tags': tags, 'distance_m': dist,
                'external_id': external_id, 'score': float(score)
            }
    return best

def fetch_highway_by_name(name):
    q = f"""
    [out:json][timeout:120];
    (
      relation["name"="{name}"]["highway"];
      relation["name"="{name}"]["route"="road"];
      way["name"="{name}"]["highway"];
    );
    out geom;
    """
    try:
        r = requests.post(OVERPASS_URL, data={'data': q})
        if r.status_code != 200: return None
        return r.json()
    except Exception: return None

def flatten_geometry(el):
    geom = el.get('geometry')
    if not geom: return None
    return [(p['lat'], p['lon']) for p in geom]

def main(args):
    print("Starting Nationwide POI Generation...", file=sys.stderr)
    out_f = open(args.output, 'w', encoding='utf-8')
    inserted = set()
    now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
    
    for hw in HIGHWAY_NAMES:
        print(f"Processing highway: {hw}", file=sys.stderr)
        res = fetch_highway_by_name(hw)
        if not res or 'elements' not in res or not res['elements']:
            print(f"  No geometry for {hw}", file=sys.stderr)
            continue
            
        el = max(res['elements'], key=lambda e: len(e.get('geometry',[])))
        coords = flatten_geometry(el)
        if not coords or len(coords)<2: continue
        
        sample_pts = sample_line_by_distance(coords, SEGMENT_METERS)
        print(f"  Segments: {len(sample_pts)}", file=sys.stderr)
        
        # Limit segments for faster SAMPLE run if needed (remove limit for full run)
        # sample_pts = sample_pts[:20] 
        
        for idx, (plat, plon) in enumerate(sample_pts):
            seg_name = f"{hw}:km_{idx*10}-{(idx+1)*10}"
            print(f"    Scanning segment: {seg_name}", file=sys.stderr)
            for cat in CATEGORIES.keys():
                try:
                    over = overpass_query_around(plat, plon, SEARCH_RADIUS_METERS, CATEGORIES[cat]['overpass_tag'])
                    best = pick_best_poi(over, plat, plon, cat)
                    if not best: continue
                    
                    key = f"{round(best['lat'],5)}/{round(best['lon'],5)}/{cat}"
                    if key in inserted: continue
                    inserted.add(key)
                    
                    name = best['tags'].get('name') or (f"{cat.upper()} POI")
                    brand = best['tags'].get('brand') or ''
                    operator = best['tags'].get('operator') or ''
                    phone = best['tags'].get('phone') or ''
                    website = best['tags'].get('website') or ''
                    tags_json = json.dumps(best['tags'], ensure_ascii=False).replace("'", "''")
                    external_id = best['external_id']
                    confidence = round(best['score'],3)
                    
                    # Safer string formatting
                    name_safe = name.replace("'","''")
                    brand_safe = brand.replace("'","''")
                    op_safe = operator.replace("'","''")
                    
                    insert = (
                        f"INSERT INTO pois ("
                        f"name, latitude, longitude, category, route_segment, created_at, "
                        f"external_id, source, brand, operator, phone, website, tags, "
                        f"confidence, verified, updated_at) VALUES ("
                        f"'{name_safe}', {best['lat']}, {best['lon']}, '{cat}', '{seg_name}', '{now}', "
                        f"'{external_id}', 'osm_overpass', '{brand_safe}', '{op_safe}', '{phone}', "
                        f"'{website}', '{tags_json}', {confidence}, 0, '{now}');\n"
                    )
                    out_f.write(insert)
                    
                    time.sleep(2.0) # increased rate limit for stability
                except Exception as e:
                    print(f"    Error in loop: {e}", file=sys.stderr)
                    
    out_f.close()
    print(f"Done. Output: {args.output}", file=sys.stderr)

if __name__ == "__main__":
    p = argparse.ArgumentParser()
    p.add_argument('--output', default='pois_dump.sql')
    args = p.parse_args()
    main(args)
