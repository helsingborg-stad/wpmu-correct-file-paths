<?php

/*
Plugin Name:    WPMU Correct File Paths
Description:    Resolves incorrect file paths when migrating databases between different environments.
Version:        1.1.0
Author:         Sebastian Thulin
*/

namespace WPMUCorrectFilePaths;

class WPMUCorrectFilePaths
{

    private $key = "wp-content";

    public function __construct()
    {
        add_filter('upload_dir', [$this, 'correctFilePaths'], 1);
        add_filter('option_upload_path', [$this, 'correctFilePath'], 999);
        add_filter('kirki_downloaded_font_files', [$this, 'correctKirkiFontFiles']);
    }

    public function correctFilePath($path)
    {
        return WP_CONTENT_DIR . $this->getPathWithoutRootDirectory($path);
    }

    public function correctFilePaths($data)
    {
        if (isset($data['path'])) {
            $data['path'] = WP_CONTENT_DIR . $this->getPathWithoutRootDirectory($data['path']);
        }

        if (isset($data['basedir'])) {
            $data['basedir'] = WP_CONTENT_DIR . $this->getPathWithoutRootDirectory($data['basedir']);
        }

        return $data;
    }

    public function correctKirkiFontFiles($fontFiles)
    {
        if (is_array($fontFiles) && !empty($fontFiles)) {
            foreach ($fontFiles as &$fontFile) {
                if (is_string($fontFile)) {
                    $fontFile = $this->correctFilePath($fontFile);
                }
            }
        }

        return $fontFiles;
    }

    private function getPathWithoutRootDirectory($path)
    {
        return rtrim(trim(substr($path, strpos($path, $this->key), strlen($path)), $this->key), DIRECTORY_SEPARATOR);
    }
}

new \WPMUCorrectFilePaths\WPMUCorrectFilePaths();
