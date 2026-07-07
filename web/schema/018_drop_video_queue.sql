-- Removes the ffmpeg/HLS transcoding queue: episode uploads now publish
-- instantly (video_url is set directly at upload time), so this table and
-- its cron worker (web/cron/process-video-queue.php, deleted) are no longer
-- used. See EpisodeController::handleVideoUpload().
DROP TABLE IF EXISTS `video_queue`;
