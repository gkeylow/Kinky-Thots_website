# Responsive Image System

## Overview

Automatically generates and serves appropriately-sized images to save cellular data and improve load times.

## How It Works

### 1. Automatic Generation on Upload
When an image is uploaded, the system automatically generates 4 optimized versions:

| Size | Width | Quality | Use Case |
|------|-------|---------|----------|
| **Thumb** | 150px | 75% | Thumbnails, previews |
| **Small** | 480px | 80% | Mobile phones |
| **Medium** | 1024px | 85% | Tablets |
| **Large** | 1920px | 90% | Desktop/HD displays |

### 2. Modern Format Support
- **WebP** versions generated for browsers that support it (30-50% smaller)
- **JPEG** fallback for older browsers
- Browser automatically chooses best format

### 3. Responsive Serving
The gallery uses `<picture>` and `srcset` to serve the right size:

```html
<picture>
  <source type="image/webp" srcset="small.webp 480w, medium.webp 1024w, large.webp 1920w" />
  <img src="small.jpg" srcset="small.jpg 480w, medium.jpg 1024w, large.jpg 1920w" />
</picture>
```

Browser automatically selects based on:
- Screen size
- Device pixel ratio
- Network speed (with `loading="lazy"`)

## Data Savings

### Example: 5MB Original Image

| Device | Size Served | Data Saved |
|--------|-------------|------------|
| Mobile (480px) | ~150KB | 97% |
| Tablet (1024px) | ~400KB | 92% |
| Desktop (1920px) | ~1.2MB | 76% |

**With WebP**: Additional 30-50% savings

## File Structure

```
/var/www/kinky-thots/uploads/
├── original-image.jpg          # Original upload
└── optimized/
    ├── thumb/
    │   ├── original-image.jpg  # 150px
    │   └── original-image.webp
    ├── small/
    │   ├── original-image.jpg  # 480px
    │   └── original-image.webp
    ├── medium/
    │   ├── original-image.jpg  # 1024px
    │   └── original-image.webp
    └── large/
        ├── original-image.jpg  # 1920px
        └── original-image.webp
```

## Manual Generation

To generate responsive images for existing uploads:

```bash
# Generate for all images in uploads directory
bash /var/www/kinky-thots/scripts/generate-responsive-images.sh

# Generate for specific directory
bash /var/www/kinky-thots/scripts/generate-responsive-images.sh /path/to/images
```

## Technical Details

### Backend (`image-optimizer.js`)
- Uses **Sharp** library (faster than ImageMagick)
- Progressive JPEG encoding
- Optimized WebP compression
- Maintains aspect ratios
- Strips EXIF data for privacy

### Frontend (`gallery.js`)
- Uses `<picture>` element for format selection
- `srcset` for responsive sizing
- `sizes` attribute for layout hints
- `loading="lazy"` for deferred loading
- Fallback to original if optimized version missing

### Automatic Processing
- Triggered on image upload
- Runs in background (non-blocking)
- Logs success/failure
- Graceful degradation if generation fails

## Browser Support

| Feature | Support |
|---------|---------|
| `<picture>` | 97% of browsers |
| `srcset` | 98% of browsers |
| WebP | 95% of browsers |
| Lazy loading | 90% of browsers |

Older browsers automatically fall back to JPEG.

## Performance Impact

### Before Responsive Images
- Mobile: 5MB download
- Load time: 15-30 seconds on 3G
- High data usage

### After Responsive Images
- Mobile: 150KB download (WebP)
- Load time: 1-2 seconds on 3G
- **97% data savings**

## Configuration

Edit `/var/www/kinky-thots/backend/image-optimizer.js` to adjust:

```javascript
const SIZES = {
  thumb: { width: 150, quality: 75 },
  small: { width: 480, quality: 80 },
  medium: { width: 1024, quality: 85 },
  large: { width: 1920, quality: 90 }
};
```

## Monitoring

Check if responsive images are being generated:

```bash
# View server logs
pm2 logs server

# Check optimized directory
ls -lh /var/www/kinky-thots/uploads/optimized/small/

# Count generated images
find /var/www/kinky-thots/uploads/optimized -name "*.webp" | wc -l
```

## Troubleshooting

### Images not generating
1. Check Sharp is installed: `npm list sharp`
2. Check server logs: `pm2 logs server`
3. Verify permissions: `ls -la /var/www/kinky-thots/uploads/optimized`

### Fallback to original
- Normal behavior if optimized version doesn't exist yet
- Check browser console for 404 errors
- Verify file paths in network tab

### WebP not working
- Check browser support: https://caniuse.com/webp
- Verify WebP files exist in optimized directories
- Check `<source type="image/webp">` in HTML

## Benefits

✅ **97% data savings** on mobile  
✅ **10-30x faster** load times  
✅ **Better user experience** on slow connections  
✅ **SEO improvement** (faster page speed)  
✅ **Automatic** - no manual work needed  
✅ **Progressive enhancement** - works everywhere
