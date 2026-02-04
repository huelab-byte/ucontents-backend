<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

class CsvTemplateService
{
    /**
     * Get the path to the sample CSV file, creating it if it doesn't exist
     */
    public function getSampleCsvPath(): string
    {
        $samplePath = storage_path('app/public/templates/bulk-posting-sample.csv');

        if (!file_exists($samplePath)) {
            $this->createSampleCsvFile($samplePath);
        }

        return $samplePath;
    }

    /**
     * Create a sample CSV file for bulk posting
     */
    public function createSampleCsvFile(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // CSV columns matching MediaUpload fields:
        // - youtube_heading: Title for YouTube video (maps to youtube_heading in MediaUpload)
        // - caption: The social media post text (maps to social_caption in MediaUpload)
        // - hashtags: Space-separated hashtags (e.g., "#tag1 #tag2 #tag3")
        // - media_url: URL to the image or video file
        $content = "youtube_heading,caption,hashtags,media_url\n";
        $content .= "\"Productivity Secrets Revealed\",\"Discover the secrets of productivity! Watch our latest video to transform your daily routine into a success story. Click now to learn more!\",\"#Productivity #Success #DailyRoutine #LifeHacks #Motivation\",https://example.com/videos/productivity-tips.mp4\n";
        $content .= "\"New Collection Launch\",\"Step into a world of creativity! Our new collection is here and it's absolutely stunning. Which piece is your favorite?\",\"#NewCollection #Creative #Fashion #Style #TrendAlert\",https://example.com/images/new-collection.jpg\n";
        $content .= "\"BTS Photoshoot Magic\",\"Behind the scenes of our latest photoshoot! See how the magic happens. Swipe to see more exclusive content!\",\"#BehindTheScenes #Photoshoot #Exclusive #ContentCreation\",https://example.com/images/bts-photoshoot.jpg\n";
        $content .= "\"Big Announcement Teaser\",\"Big announcement coming your way! Stay tuned for something exciting. Drop a comment if you're ready!\",\"#Announcement #ComingSoon #StayTuned #Excited\",https://example.com/images/announcement-teaser.jpg\n";
        $content .= "\"Home Decor Transformation\",\"Transform your space with these simple tips! Watch the full tutorial on our channel. Link in bio!\",\"#HomeDecor #InteriorDesign #DIY #HomeTips #Transformation\",https://example.com/videos/home-decor-tips.mp4\n";

        file_put_contents($path, $content);
    }

    /**
     * Get sample CSV content as a string (without creating file)
     */
    public function getSampleCsvContent(): string
    {
        $content = "youtube_heading,caption,hashtags,media_url\n";
        $content .= "\"Productivity Secrets Revealed\",\"Discover the secrets of productivity! Watch our latest video to transform your daily routine into a success story. Click now to learn more!\",\"#Productivity #Success #DailyRoutine #LifeHacks #Motivation\",https://example.com/videos/productivity-tips.mp4\n";
        $content .= "\"New Collection Launch\",\"Step into a world of creativity! Our new collection is here and it's absolutely stunning. Which piece is your favorite?\",\"#NewCollection #Creative #Fashion #Style #TrendAlert\",https://example.com/images/new-collection.jpg\n";
        $content .= "\"BTS Photoshoot Magic\",\"Behind the scenes of our latest photoshoot! See how the magic happens. Swipe to see more exclusive content!\",\"#BehindTheScenes #Photoshoot #Exclusive #ContentCreation\",https://example.com/images/bts-photoshoot.jpg\n";
        $content .= "\"Big Announcement Teaser\",\"Big announcement coming your way! Stay tuned for something exciting. Drop a comment if you're ready!\",\"#Announcement #ComingSoon #StayTuned #Excited\",https://example.com/images/announcement-teaser.jpg\n";
        $content .= "\"Home Decor Transformation\",\"Transform your space with these simple tips! Watch the full tutorial on our channel. Link in bio!\",\"#HomeDecor #InteriorDesign #DIY #HomeTips #Transformation\",https://example.com/videos/home-decor-tips.mp4\n";

        return $content;
    }
}
