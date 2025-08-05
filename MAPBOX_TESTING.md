# Testing Mapbox Integration

## Current Status
- ✅ Mapbox GL JS added via CDN (no npm install needed)
- ✅ 8 test places with coordinates in San Francisco
- ✅ API endpoint working at `/api/places/map-data`
- ✅ All Vue components created

## Testing Steps

### 1. Start Both Servers
```bash
# Terminal 1 - Laravel API
php artisan serve

# Terminal 2 - Vue Frontend
cd frontend
npm run dev
```

### 2. Access the Map Page
Navigate to: http://localhost:5173/places/map

### 3. What You Should See
- Interactive map centered on USA
- Loading spinner briefly
- Map controls in top-right corner
- View mode toggle in top-left
- Search bar at the top

### 4. Verify Places Are Loading
1. Open browser Developer Tools (F12)
2. Go to Network tab
3. Look for request to `map-data`
4. Should return 8 places in San Francisco

### 5. Navigate to San Francisco
Since the places are in San Francisco, you need to:
1. Zoom out to see the USA
2. Navigate to San Francisco (West Coast)
3. Or use the search to go to "San Francisco"
4. You should see 8 blue dots representing the attractions

## Troubleshooting

### If Map Doesn't Load
1. Check browser console for errors
2. Verify `window.mapboxgl` exists in console
3. Clear browser cache (Cmd+Shift+R)
4. Check that Mapbox token is correct in .env

### If No Places Appear
1. Verify API is running: http://localhost:8000/api/places/map-data?bounds[north]=37.85&bounds[south]=37.75&bounds[east]=-122.35&bounds[west]=-122.50
2. Check Network tab for API errors
3. Zoom into San Francisco area (37.7749, -122.4194)

### Console Commands to Debug
```javascript
// Check if Mapbox is loaded
console.log(window.mapboxgl)

// Check current map bounds
// (run this in console after map loads)
const map = document.querySelector('.mapboxgl-map').__vue__
console.log(map)
```

## Quick Location Test
To quickly jump to where the places are, you can:
1. Click the location button (if you're in SF area)
2. Or manually pan/zoom to San Francisco
3. Coordinates: Lat 37.7749, Lng -122.4194

## API Test
You can test the API directly in your browser:
http://localhost:8000/api/places/map-data?bounds[north]=40&bounds[south]=30&bounds[east]=-70&bounds[west]=-130

This should return all 8 places since it covers most of the USA.