/**
 * Image compression utility for client-side image optimization
 */

/**
 * Compress an image file before upload
 * @param {File} file - The image file to compress
 * @param {Object} options - Compression options
 * @param {number} options.maxWidth - Maximum width (default: 2048)
 * @param {number} options.maxHeight - Maximum height (default: 2048)
 * @param {number} options.quality - JPEG quality 0-1 (default: 0.85)
 * @param {string} options.outputType - Output format (default: 'image/jpeg')
 * @returns {Promise<File>} - Compressed image file
 */
export async function compressImage(file, options = {}) {
    const {
        maxWidth = 2048,
        maxHeight = 2048,
        quality = 0.85,
        outputType = 'image/jpeg'
    } = options;

    // Skip compression for SVG files
    if (file.type === 'image/svg+xml') {
        return file;
    }

    // Skip if file is already small (under 500KB)
    if (file.size < 500 * 1024) {
        return file;
    }

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const img = new Image();
            
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Calculate new dimensions
                let { width, height } = img;
                
                // Scale down if needed
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }
                
                // Set canvas size
                canvas.width = width;
                canvas.height = height;
                
                // Draw image on canvas
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert to blob
                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            // Create new file from blob
                            const compressedFile = new File(
                                [blob],
                                file.name,
                                {
                                    type: outputType,
                                    lastModified: Date.now()
                                }
                            );
                            
                            // Only use compressed version if it's smaller
                            if (compressedFile.size < file.size) {
                                resolve(compressedFile);
                            } else {
                                resolve(file);
                            }
                        } else {
                            reject(new Error('Failed to compress image'));
                        }
                    },
                    outputType,
                    quality
                );
            };
            
            img.onerror = () => {
                reject(new Error('Failed to load image'));
            };
            
            img.src = e.target.result;
        };
        
        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };
        
        reader.readAsDataURL(file);
    });
}

/**
 * Compress multiple images
 * @param {File[]} files - Array of image files
 * @param {Object} options - Compression options
 * @returns {Promise<File[]>} - Array of compressed files
 */
export async function compressImages(files, options = {}) {
    return Promise.all(files.map(file => compressImage(file, options)));
}

/**
 * Get image dimensions from file
 * @param {File} file - Image file
 * @returns {Promise<{width: number, height: number}>}
 */
export async function getImageDimensions(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const img = new Image();
            
            img.onload = () => {
                resolve({
                    width: img.width,
                    height: img.height
                });
            };
            
            img.onerror = () => {
                reject(new Error('Failed to load image'));
            };
            
            img.src = e.target.result;
        };
        
        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };
        
        reader.readAsDataURL(file);
    });
}