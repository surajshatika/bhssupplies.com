<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Upload;
use Image;
use Storage;

class ConvertImagesToWebP extends Command
{
    protected $signature   = 'images:convert-webp
                                {--limit=100 : Number of images to process per run}
                                {--quality=80 : WebP quality 1-100}';

    protected $description = 'Convert existing JPG/PNG uploads to WebP format and save alongside originals';

    public function handle()
    {
        $limit   = (int) $this->option('limit');
        $quality = (int) $this->option('quality');

        $uploads = Upload::whereIn('extension', ['jpg', 'jpeg', 'png'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        if ($uploads->isEmpty()) {
            $this->info('No images to convert.');
            return 0;
        }

        $converted = 0;
        $skipped   = 0;
        $failed    = 0;

        $bar = $this->output->createProgressBar($uploads->count());
        $bar->start();

        foreach ($uploads as $upload) {
            $originalPath = public_path($upload->file_name);

            if (!file_exists($originalPath)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalPath);

            if (file_exists($webpPath)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                Image::make($originalPath)->encode('webp', $quality)->save($webpPath);
                $converted++;
            } catch (\Exception $e) {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Converted: {$converted} | Skipped: {$skipped} | Failed: {$failed}");

        return 0;
    }
}
