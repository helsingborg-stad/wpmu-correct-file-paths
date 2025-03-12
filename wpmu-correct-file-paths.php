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
    public function __construct()
    {
        //Correct values that may be faulty in db
        add_filter('option_upload_path', function($optionValue) {
            if(!empty($optionValue)) {
                return WP_CONTENT_DIR . $this->getRelativePath($optionValue); 
            }
            return $optionValue;
        }, 1, 1);

        //Warn about UPLOADS constant
        add_action('admin_notices', function() {
            if(defined('UPLOADS')) {
                printf( '<div class="%1$s"><p>%2$s</p></div>',
                    esc_attr('notice notice-error'), 
                    esc_html("Please do not define UPLOADS constant in Municipio; This is not supported.")
                );
            }
        });
    }

    /**
     * Get relative path
     * 
     * @param string    $path   The path to a file or asset, with domain or root directory.
     * @return string           The relative path to the file or asset. 
     */
    private function getRelativePath(string $path): string
    {
        $pattern = '/(\/wp-content\/)/';
        if (preg_match($pattern, $path, $matches, PREG_OFFSET_CAPTURE)) {
            $delimiterPos = $matches[0][1] + strlen($matches[0][0]);
            $relativePath = substr($path, $delimiterPos);
            return '/' . ltrim($relativePath, '/');
        }
        return $path;
    }
}

new \WPMUCorrectFilePaths\WPMUCorrectFilePaths();