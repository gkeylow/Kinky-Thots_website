const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

// Image size configurations
const SIZES = {
  thumb: { width: 150, quality: 75 },
  small: { width: 480, quality: 80 },
  medium: { width: 1024, quality: 85 },
  large: { width: 1920, quality: 90 }
};

/**
 * Generate responsive image versions
 * @param {string} inputPath - Path to original image
 * @param {string} outputDir - Directory to save optimized versions
 * @returns {Promise<Object>} - Paths to generated images
 */
async function generateResponsiveImages(inputPath, outputDir) {
  const filename = path.basename(inputPath);
  const name = path.parse(filename).name;
  const ext = path.parse(filename).ext.toLowerCase();
  
  // Skip if not an image
  if (!['.jpg', '.jpeg', '.png', '.gif', '.webp'].includes(ext)) {
    return null;
  }
  
  // Create output directories
  const dirs = ['thumb', 'small', 'medium', 'large'];
  dirs.forEach(dir => {
    const dirPath = path.join(outputDir, dir);
    if (!fs.existsSync(dirPath)) {
      fs.mkdirSync(dirPath, { recursive: true });
    }
  });
  
  const results = {};
  
  try {
    // Get original image metadata
    const metadata = await sharp(inputPath).metadata();
    const originalWidth = metadata.width;
    
    // Generate each size
    for (const [sizeName, config] of Object.entries(SIZES)) {
      // Skip if original is smaller than target size
      if (originalWidth <= config.width && sizeName !== 'thumb') {
        continue;
      }
      
      const outputPath = path.join(outputDir, sizeName, `${name}.jpg`);
      const webpPath = path.join(outputDir, sizeName, `${name}.webp`);
      
      // Generate JPEG version
      await sharp(inputPath)
        .resize(config.width, null, {
          withoutEnlargement: true,
          fit: 'inside'
        })
        .jpeg({ quality: config.quality, progressive: true })
        .toFile(outputPath);
      
      // Generate WebP version (better compression)
      await sharp(inputPath)
        .resize(config.width, null, {
          withoutEnlargement: true,
          fit: 'inside'
        })
        .webp({ quality: config.quality })
        .toFile(webpPath);
      
      results[sizeName] = {
        jpg: outputPath,
        webp: webpPath,
        width: config.width
      };
    }
    
    return results;
  } catch (error) {
    console.error('Error generating responsive images:', error);
    return null;
  }
}

/**
 * Get responsive image URLs for a filename
 * @param {string} filename - Original filename
 * @param {string} webPath - Web path prefix
 * @returns {Object} - srcset and sizes for responsive images
 */
function getResponsiveImageUrls(filename, webPath = '/uploads') {
  const name = path.parse(filename).name;
  
  return {
    thumb: {
      jpg: `${webPath}/optimized/thumb/${name}.jpg`,
      webp: `${webPath}/optimized/thumb/${name}.webp`
    },
    small: {
      jpg: `${webPath}/optimized/small/${name}.jpg`,
      webp: `${webPath}/optimized/small/${name}.webp`
    },
    medium: {
      jpg: `${webPath}/optimized/medium/${name}.jpg`,
      webp: `${webPath}/optimized/medium/${name}.webp`
    },
    large: {
      jpg: `${webPath}/optimized/large/${name}.jpg`,
      webp: `${webPath}/optimized/large/${name}.webp`
    },
    original: `${webPath}/${filename}`
  };
}

/**
 * Generate srcset string for responsive images
 * @param {string} filename - Original filename
 * @param {string} webPath - Web path prefix
 * @returns {string} - srcset attribute value
 */
function generateSrcset(filename, webPath = '/uploads') {
  const name = path.parse(filename).name;
  const sizes = [];
  
  Object.entries(SIZES).forEach(([sizeName, config]) => {
    sizes.push(`${webPath}/optimized/${sizeName}/${name}.jpg ${config.width}w`);
  });
  
  return sizes.join(', ');
}

module.exports = {
  generateResponsiveImages,
  getResponsiveImageUrls,
  generateSrcset,
  SIZES
};
