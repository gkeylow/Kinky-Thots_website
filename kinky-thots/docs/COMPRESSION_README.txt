VIDEO COMPRESSION SCRIPT - READY TO USE
========================================

LOCATION: /var/www/kinky-thots/compress-videos.sh

WHAT IT DOES:
- Finds videos larger than 200MB in /media/porn
- Compresses them to ~100-200MB (70-85% size reduction)
- Maintains excellent quality (visually identical)
- Backs up originals to /media/porn/originals/
- Replaces with compressed versions

HOW TO USE:
-----------
cd /var/www/kinky-thots
./compress-videos.sh

SETTINGS:
---------
- Quality: CRF 23 (good balance)
- Resolution: Max 1080p
- Audio: 128k AAC
- Only compresses videos > 200MB

EXPECTED RESULTS:
-----------------
680MB video → 120MB (82% savings)
897MB video → 150MB (83% savings)
580MB video → 110MB (81% savings)

BENEFITS:
---------
✓ 70-85% bandwidth savings
✓ Faster page loads
✓ Better mobile experience
✓ More storage space
✓ Lower hosting costs

SAFETY:
-------
✓ Originals backed up automatically
✓ Can restore from /media/porn/originals/
✓ Test quality before deleting backups

NEXT STEPS:
-----------
1. Run: ./compress-videos.sh
2. Test compressed videos on porn.php
3. If quality good, keep compressed versions
4. Delete backups after 1-2 weeks if satisfied
