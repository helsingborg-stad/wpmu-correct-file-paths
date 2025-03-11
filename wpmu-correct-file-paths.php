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
    const RELATIVE_DELIMITER_CONTENT = "wp-content";
    const RELATIVE_DELIMITER_INCLUDES = "wp-includes";

    public function __construct()
    {
        // Define keys if undefined
        $this->addInitHooks();

        // Do string filtering
        $this->addSanitizationFilters();
    }

    /**
     * Add init hooks
     * 
     * @return void
     */
    public function addInitHooks(): void
    {
        add_action('plugins_loaded', [$this, 'setContentDir']);
        add_action('plugins_loaded', [$this, 'setContentUrl']);
    }

    /**
     * Define WP_CONTENT_DIR if not already defined
     * 
     * @return void
     */
    public function setContentDir(): void
    {
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
        }
    }

    /**
     * Define WP_CONTENT_URL if not already defined
     * 
     * @return void
     */
    public function setContentUrl(): void
    {
        if (!defined('WP_CONTENT_URL')) {
            define('WP_CONTENT_URL', $this->getHomeUrlWithoutPath() . '/wp-content');
        }
    }

    /**
     * Add sanitization filters
     * 
     * @return void
     */
    private function addSanitizationFilters(): void
    {
        add_filter('upload_dir', [$this, 'correctFilePathsInUploadDirFilter'], 1);
        add_filter('option_upload_path', [$this, 'correctFilePath']);
        add_filter('option_kirki_downloaded_font_files', [$this, 'correctKirkiFontFiles']);
        add_filter('includes_url', [$this, 'correctIncludesUrl'], 999, 3);
    }

    /**
     * Get content url
     * 
     * @return string
     */
    private function getContentUrl(): string
    {
        return WP_CONTENT_URL;
    }

    /**
     * Get content dir
     * 
     * @return string
     */
    private function getContentDir(): string
    {
        return WP_CONTENT_DIR;
    }

    /**
     * Get absolute path
     * 
     * @return string
     */
    private function getAbsPath(): string
    {
        return ABSPATH; 
    }

    /**
     * Get wp includes
     * 
     * @return string
     */
    private function getWpInc(): string
    {
        return WPINC;
    }

    /**
     * Get home url without path
     * 
     * @return string
     */
    private function getHomeUrlWithoutPath(): string
    {
        $homeUrl = home_url();
        $parsedUrl = parse_url($homeUrl);
    
        // Construct the base URL without path
        return isset($parsedUrl['scheme'], $parsedUrl['host']) 
            ? $parsedUrl['scheme'] . '://' . $parsedUrl['host']
            : $homeUrl; 
    }

    /**
     * Get relative path
     * 
     * @param string    $path   The path to a file or asset, with domain or root directory.
     * @return string           The relative path to the file or asset. 
     */
    private function getRelativePath(string $path): string
    {
        $pattern = '/(\/'.self::RELATIVE_DELIMITER_CONTENT.'\/|\/'.self::RELATIVE_DELIMITER_INCLUDES.'\/)/';
        if (preg_match($pattern, $path, $matches, PREG_OFFSET_CAPTURE)) {
            $delimiterPos = $matches[0][1] + strlen($matches[0][0]);
            $relativePath = substr($path, $delimiterPos);
            return '/' . ltrim($relativePath, '/');
        }
        return $path;
    }

    /**
     * Get absolute path
     * 
     * @param string    $path   The path to a file or asset, with domain or root directory.
     * @return string           The absolute path to the file or asset. 
     */
    private function getAbsolutePath(string $path): string
    {
        return $this->getContentDir() . $this->getRelativePath($path);
    }

    /**
     * Get absolute url
     * 
     * @param string    $url    The url to a file or asset, with domain or root directory.
     * @return string           The absolute url to the file or asset. 
     */
    private function getAbsoluteUrl(string $url): string
    {
        return $this->getContentUrl() . $this->getRelativePath($url);
    }

    /**
     * Correct includes url
     * 
     * @param string    $url    The url to a file or asset, with domain or root directory.
     * @param string    $path   The path to a file or asset, with domain or root directory.
     * @param string    $scheme Originally the scheme for the url. This will only take https/http or relative. 
     *                          Url's with prefixes, will always be returned as the sites main protocol. 
     * @return string           The corrected url to the file or asset. 
     */
    public function correctIncludesUrl($url, $path, $scheme)
    {
        $url = str_replace(
            $_SERVER['DOCUMENT_ROOT'] ?? '', 
            '',
            $this->getAbsPath() . $this->getWpInc() . $this->getRelativePath($url)
        );
        
        if($scheme !== 'relative') {
            $url = $this->getHomeUrlWithoutPath() . $url;
        }

        return $url; 
    }

    /**
     * Correct file path
     * 
     * @param string    $path   The path to a file or asset, with domain or root directory.
     * @return string           The corrected path to the file or asset. 
     */
    public function correctFilePath($path)
    {
        return $this->getAbsolutePath($path);
    }

    /**
     * Correct file paths in upload dir filter
     * 
     * @param array     $data   The data to correct file paths in.
     * 
     * @return array            The corrected data.
     */
    public function correctFilePathsInUploadDirFilter($data)
    {
        if (isset($data['path'])) {
            $data['path'] = $this->getContentDir();
        }

        if (isset($data['basedir'])) {
            $data['basedir'] = $this->correctFilePath($data['basedir']);
        }

        if (isset($data['url'])) {
            $data['url'] = $this->getContentUrl();
        }

        if (isset($data['baseurl'])) {
            $data['baseurl'] = $this->getAbsoluteUrl($data['baseurl']);
        }

        return $data;
    }

    /**
     * Correct Kirki font files
     * 
     * @param array     $fontFiles  The font files to correct.
     * @return array                The corrected font files.
     */
    public function correctKirkiFontFiles($fontFiles): array
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
}

new \WPMUCorrectFilePaths\WPMUCorrectFilePaths();
