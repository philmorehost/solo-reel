<?php

require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

// Ensure we only run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

$db = Database::getInstance();

// Find one pending job
$stmt = $db->prepare("SELECT * FROM video_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
$stmt->execute();
$job = $stmt->fetch();

if (!$job) {
    echo "No pending jobs.\n";
    exit(0);
}

// Mark as processing
$stmt = $db->prepare("UPDATE video_queue SET status = 'processing', started_at = NOW() WHERE id = ?");
$stmt->execute([$job['id']]);

$videoId = $job['episode_id'];
$inputFile = __DIR__ . '/../' . $job['original_file'];

if (!file_exists($inputFile)) {
    $error = "Input file not found: " . $inputFile;
    $stmt = $db->prepare("UPDATE video_queue SET status = 'failed', error_message = ? WHERE id = ?");
    $stmt->execute([$error, $job['id']]);
    die($error . "\n");
}

// Prepare HLS directory
$hlsDir = __DIR__ . '/../storage/hls/' . $videoId;
if (!is_dir($hlsDir)) {
    mkdir($hlsDir, 0775, true);
}

// We will do a single 720p conversion for simplicity, adaptive bitrate can be expanded here
$outputPlaylist = $hlsDir . '/master.m3u8';
$segmentPattern = $hlsDir . '/segment_%03d.ts';

// FFmpeg command optimized for vertical video (scaling width proportionally to 720p height)
$ffmpegCmd = "ffmpeg -y -i " . escapeshellarg($inputFile) . " " .
    "-vf \"scale=-2:720\" " .
    "-c:v libx264 -crf 23 -preset fast " .
    "-c:a aac -b:a 128k " .
    "-hls_time 6 " .
    "-hls_list_size 0 " .
    "-hls_segment_filename " . escapeshellarg($segmentPattern) . " " .
    escapeshellarg($outputPlaylist) . " 2>&1";

echo "Running command: " . $ffmpegCmd . "\n";
exec($ffmpegCmd, $output, $returnCode);

if ($returnCode === 0) {
    // Generate thumbnail at 5s mark
    $thumbCmd = "ffmpeg -y -i " . escapeshellarg($inputFile) . " -ss 00:00:05.000 -vframes 1 " . escapeshellarg($hlsDir . '/thumbnail.jpg') . " 2>&1";
    exec($thumbCmd);

    $hlsUrl = '/storage/hls/' . $videoId . '/master.m3u8';

    // Mark done
    $stmt = $db->prepare("UPDATE video_queue SET status = 'done', completed_at = NOW(), hls_path = ?, hls_url = ?, progress = 100 WHERE id = ?");
    $stmt->execute([$hlsDir, $hlsUrl, $job['id']]);

    // Update episode record
    $stmt = $db->prepare("UPDATE episodes SET video_url = ?, thumbnail_url = ? WHERE id = ?");
    $stmt->execute([$hlsUrl, '/storage/hls/' . $videoId . '/thumbnail.jpg', $videoId]);

    echo "Job {$job['id']} completed successfully.\n";
} else {
    $error = implode("\n", $output);
    $stmt = $db->prepare("UPDATE video_queue SET status = 'failed', error_message = ? WHERE id = ?");
    $stmt->execute([$error, $job['id']]);
    echo "Job {$job['id']} failed: " . $error . "\n";
}

// Optional: clean up original file
// unlink($inputFile);
