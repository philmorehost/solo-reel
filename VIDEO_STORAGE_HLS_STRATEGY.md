# SOLOSHORT — Video Storage & HLS Conversion Strategy

## The Question

> Video streaming HLS: Will users upload raw MP4s and the server converts to HLS, or will the script expect pre-converted HLS URLs/folders to be uploaded?

**Context:** The site is hosted on a VPS or dedicated server (not shared cPanel).

---

## Option A: Server-side FFmpeg Conversion (Recommended)

Admin uploads MP4 → Server converts to HLS (`.m3u8` + `.ts` segments) → Stream from server or CDN.

**This is what YouTube, Vimeo, and every major platform does internally.**

## Option B: Upload Pre-converted HLS

Admin uploads a complete HLS folder (`.m3u8` manifest + dozens of `.ts` segment files). A single 10-minute video generates 60+ segment files — impractical for a CMS workflow.

---

## Recommendation: Server Storage + Cloudflare CDN

### Why This Setup

The dedicated server has **8TB of local storage** — roughly 4,000 hours of HLS video at 3 quality variants. Capacity is not the constraint. The constraint is **delivery bandwidth**: if 100 people stream simultaneously, your server's network port gets saturated. The CDN solves this.

### Architecture Overview

```
Viewer → Cloudflare CDN (cached) → Your server (origin, only on cache miss)

Admin uploads MP4 → Server FFmpeg converts to HLS → HLS stored on server disk →
Cloudflare caches and delivers to viewers worldwide
```

### Why NOT Google Drive

Google Drive is a file sync/backup service, not a streaming origin:
- Rate-limits HTTP requests — HLS creates dozens of `.ts` segment requests per viewer
- No CDN edge caching — every viewer hits Google's API
- No custom CORS headers for cross-origin video playback
- Terms of Service prohibit using it as a streaming CDN

### Why NOT External Object Storage (S3, B2, R2)

With 8TB already available on the server, external storage adds complexity and cost with no benefit:
- Adds API latency on every segment fetch
- Introduces another billing relationship and failure point
- Server disk I/O is faster than any over-the-network storage API

Object storage becomes useful only if you outgrow 8TB or need multi-region redundancy. At that scale, revisit.

### The CDN Setup (Cloudflare, Free)

Cloudflare's free plan includes unlimited bandwidth. HLS `.m3u8` and `.ts` files are static — Cloudflare caches them automatically. After the first viewer in each geographic region fetches a file from your server, Cloudflare's 300+ edge locations serve subsequent viewers.

**Result:** 10,000 viewers streaming the same episode generate ~50 origin requests to your server, not 10,000.

### Storage Strategy (Simplified)

| Layer | Where | Why |
|-------|-------|-----|
| **Ingest** | Server temp directory during upload | Web server handles multipart uploads directly |
| **Processing** | Server disk — FFmpeg reads & writes locally | Fastest possible I/O, no network overhead |
| **Storage** | Server disk — `/storage/hls/{video_id}/` | 8TB capacity, free, zero latency |
| **Delivery** | Cloudflare CDN → viewer | 300+ edge locations, unlimited bandwidth on free plan |
| **Backup** | Nightly rclone sync to Backblaze B2 or any S3 bucket | Disaster recovery only, not live serving |

### What This Costs

| Component | Monthly Cost |
|-----------|-------------|
| Storage (8TB server disk) | $0 (included with server) |
| Bandwidth (server port) | $0 (included with server) |
| Cloudflare CDN | $0 (free plan) |
| Backup (Backblaze B2, 5TB) | ~$30/month |
| **Total** | **~$30/month** (backup only; everything else is free) |

---

## Phase 1: Server Storage + FFmpeg Queue

### Implementation

1. Admin uploads MP4 via the admin episode form (handles files up to 2GB)
2. PHP saves to `storage/temp/` and inserts a row in `video_queue` with status `pending`
3. A cron job runs every minute:
   - Picks `pending` jobs
   - Calls `ffmpeg` via `exec()` (vertical-optimized command)
   - Converts MP4 to HLS in `storage/hls/{video_id}/`
   - Updates status to `done`, stores `.m3u8` URL
4. Episode player loads the `.m3u8` URL via Cloudflare CDN

### Nginx + Cloudflare HLS Serving

Nginx serves `.m3u8` and `.ts` files as static content — no special module needed. Then Cloudflare caches them:

1. Point your domain's DNS nameservers to Cloudflare (free)
2. Set Cloudflare SSL/TLS mode to **Full**
3. Create a **Page Rule** for `/storage/hls/*`:
   - Cache Level: **Cache Everything**
   - Edge Cache TTL: **7 days**
4. That's it — Cloudflare now caches every HLS segment at 300+ edge locations

### HLS Output Directory Structure

```
storage/hls/{video_id}/
├── master.m3u8          ← Master playlist (adaptive bitrate manifest)
├── 720p.m3u8            ← 720p variant playlist
├── 720p_001.ts          ← 720p segments (6 seconds each)
├── 720p_002.ts
├── ...
├── 480p.m3u8
├── 480p_001.ts
├── ...
├── 360p.m3u8
├── 360p_001.ts
├── ...
└── thumbnail.jpg        ← Auto-generated at 5s mark
```

### Required Database Table

```sql
CREATE TABLE `video_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `original_file` varchar(500) NOT NULL,
  `status` enum('pending','processing','done','failed') DEFAULT 'pending',
  `progress` tinyint(3) DEFAULT 0,
  `hls_path` varchar(500) DEFAULT NULL,
  `hls_url` varchar(500) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `file_size_bytes` bigint(20) DEFAULT 0,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_episode_id` (`episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Cron Job

```cron
* * * * * /usr/bin/php /home/soloshort/public_html/cron/process-video-queue.php >> /home/soloshort/logs/video-queue.log 2>&1
```

### FFmpeg Command

```bash
# Single quality (720p)
ffmpeg -i input.mp4 \
  -vf "scale=-2:720" \
  -c:v libx264 -crf 23 -preset fast \
  -c:a aac -b:a 128k \
  -hls_time 6 \
  -hls_list_size 0 \
  -hls_segment_filename "output/segment_%03d.ts" \
  output/master.m3u8
```

```bash
# Adaptive bitrate (720p + 480p + 360p)
ffmpeg -i input.mp4 \
  -vf "scale=-2:720" -c:v libx264 -crf 23 -preset fast -c:a aac -b:a 128k \
  -hls_time 6 -hls_list_size 0 -hls_segment_filename "output/720p_%03d.ts" \
  output/720p.m3u8 \
  -vf "scale=-2:480" -c:v libx264 -crf 23 -preset fast -c:a aac -b:a 96k \
  -hls_time 6 -hls_list_size 0 -hls_segment_filename "output/480p_%03d.ts" \
  output/480p.m3u8 \
  -vf "scale=-2:360" -c:v libx264 -crf 23 -preset fast -c:a aac -b:a 64k \
  -hls_time 6 -hls_list_size 0 -hls_segment_filename "output/360p_%03d.ts" \
  output/360p.m3u8
```

```bash
# Master playlist (adaptive bitrate)
# Create manually or via script:
cat > output/master.m3u8 << 'EOF'
#EXTM3U
#EXT-X-STREAM-INF:BANDWIDTH=2800000,RESOLUTION=1280x720
720p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=1400000,RESOLUTION=854x480
480p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=800000,RESOLUTION=640x360
360p.m3u8
EOF
```

### Conversion Speed Estimates

| CPU Cores | 10-min Video | 30-min Video | 60-min Video |
|-----------|-------------|-------------|-------------|
| 2 cores | ~15-20 min | ~45-60 min | ~90-120 min |
| 4 cores | ~8-12 min | ~25-35 min | ~50-70 min |
| 8 cores | ~5-8 min | ~15-20 min | ~30-40 min |

Recommendation: 4+ CPU cores for a production VPS.

---

## Phase 2: CDN Configuration (Cloudflare)

### Setup Steps

1. Sign up at [cloudflare.com](https://cloudflare.com) (free)
2. Add your domain — Cloudflare scans existing DNS records
3. Change your domain's nameservers to Cloudflare's (provided during setup)
4. Go to **SSL/TLS** → set to **Full**
5. Go to **Rules** → **Page Rules** → Create Rule:
   - URL: `yourdomain.com/storage/hls/*`
   - **Cache Level**: Cache Everything
   - **Edge Cache TTL**: 7 days
6. Verify: Open a `.ts` segment URL in your browser with DevTools open — check `CF-Cache-Status: HIT` on repeat loads

### What Cloudflare Does

| Before CDN | After CDN |
|-----------|----------|
| Every viewer hits your server | First viewer per region hits server; rest get cached |
| Server pays bandwidth cost | Cloudflare absorbs 99% of bandwidth (free) |
| Slow for viewers far from server | Served from nearest edge (10-50ms latency) |
| Server CPU used for static file I/O | Server CPU free for PHP + FFmpeg |

---

## Phase 3: Backup & Optimization

### Nightly Backup (rclone)

```bash
# Install rclone
curl https://rclone.org/install.sh | sudo bash

# Configure Backblaze B2 backend
rclone config

# Nightly cron (2 AM)
0 2 * * * rclone sync /home/soloshort/public_html/storage/hls b2:soloshort-hls-backup --transfers 4 >> /var/log/hls-backup.log 2>&1
```

### Optional: Disk Usage Monitoring

```bash
# Add to crontab - runs daily at 8 AM
0 8 * * * du -sh /home/soloshort/public_html/storage/hls >> /var/log/hls-usage.log
```

Alerts you when approaching 80% capacity (6.4TB of 8TB). At that point, either upgrade the server disk or add an archival strategy for old content.

---

## Implementation Checklist

### Phase 1 (Server + FFmpeg)

- [ ] Install FFmpeg on server (`apt install ffmpeg` or `yum install ffmpeg`)
- [ ] Create `video_queue` table
- [ ] Create `cron/process-video-queue.php` worker script
- [ ] Update admin episode form to handle large file uploads (chunked upload for files >100MB)
- [ ] Add upload progress UI in admin
- [ ] Configure PHP `upload_max_filesize` = `2G` and `post_max_size` = `2G`
- [ ] Configure Nginx `client_max_body_size 2G`
- [ ] Set up cron job
- [ ] Test with a sample vertical MP4 video

### Phase 2 (Cloudflare CDN)

- [ ] Sign up for Cloudflare free plan
- [ ] Point domain DNS to Cloudflare nameservers
- [ ] Set SSL/TLS to Full
- [ ] Create Page Rule for `/storage/hls/*` (Cache Everything, 7-day TTL)
- [ ] Verify `CF-Cache-Status: HIT` on `.ts` segment requests

### Phase 3 (Backup + Polish)

- [ ] Install rclone and configure Backblaze B2 backend
- [ ] Set up nightly backup cron job
- [ ] Add multi-bitrate adaptive streaming (720p + 480p + 360p)
- [ ] Generate thumbnail from video at 5s mark
- [ ] Add WebM/VP9 encoding for non-Safari browsers (optional)
- [ ] Add DRM if licensing requires (Widevine/FairPlay) (optional)
- [ ] Set up disk usage monitoring alerts
