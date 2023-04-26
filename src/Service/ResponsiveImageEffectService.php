<?php

namespace Drupal\responsive_image_effect\Service;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\responsive_image_effect\Plugin\ImageEffect\ResponsiveImageEffect;

class ResponsiveImageEffectService {

  /**
   * Build a src set
   */
  public function makeSrcset($uri, $sizes) {
    $r = [];
    foreach ($sizes as $s) {
      if (!is_array($s)) {
        $s = ['w' => $s];
      }
      $r[] = $this->responsiveImageUrl($uri, $s) . " {$s['w']}w";
    }
    return implode(', ', $r);
  }

  /**
   * Build a URL to a responsive image style.
   *
   * @param string $source_file_uri
   *   The file uri.
   * @param array $p
   *   An array of options to apply for the responsive image.
   * @param string $image_style_name
   *   The image style name to use.
   *
   * @return string
   *   Returns a URL.
   */
  public function responsiveImageUrl($source_file_uri, array $p, $image_style_name = 'responsive') {
    $image_style = ImageStyle::load($image_style_name);

    if (empty($image_style)) {
      throw new \Exception('No such image style: ' . $image_style_name);
    }

    $width = $p['w'];
    $height = !empty($p['h']) ? $p['h'] : $p['w'];
    $crop = !empty($p['c']) ? 1 : 0;

    $derivative_uri = $this->buildUri($source_file_uri, $image_style->id(), $width, $height, $crop);

    $derivative_url = \Drupal::service('file_url_generator')->generateAbsoluteString($derivative_uri);

    // @todo security goes here.
    // phpcs:disable
    // Append the query string with the token, if necessary.
    //if ($token_query) {
    //  $derivative_url .= (strpos($derivative_url, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($token_query);
    //}
    // phpcs:enable

    return $derivative_url;
  }

  /**
   * Add crop options.
   */
  public function crop($width, $ratio = 9 / 16) {
    return ['w' => $width, 'h' => (int) ($width * $ratio), 'c' => TRUE];
  }

  /**
   * Adds crop options to each width.
   */
  public function cropAll(array $widths, $ratio = 9 / 16) {
    return array_map(function ($w) use ($ratio) {
      return $this->crop($w, $ratio);
    }, $widths);
  }

  /**
   * Build a uri to a responsive image file.
   *
   * @param string $file_uri
   *   The source file uri in the form scheme://path/file.name.
   * @param string $image_style_id
   *   The name of the image style, e.g. 'responsive'.
   * @param int $width
   *   The width to apply to the image.
   * @param int $height
   *   The height to apply to the image.
   * @param int $crop
   *   Whether to crop the image or not.
   *
   * @return string
   *   Returns an uri scheme.
   */
  public function buildUri($file_uri, $image_style_id, $width, $height, $crop) {
    $source_scheme = $scheme = StreamWrapperManager::getScheme($file_uri);
    $default_scheme = \Drupal::config('system.file')->get('default_scheme');

    if ($source_scheme) {
      $path = StreamWrapperManager::getTarget($file_uri);
      // @todo might need something in here if the source and default schemes differ.
    }
    else {
      $path = $file_uri;
      $source_scheme = $scheme = $default_scheme;
    }

    return "$scheme://styles/{$image_style_id}/{$source_scheme}/{$width}/{$height}/{$crop}/{$path}";
  }

  /**
   * Check if an image style includes a responsive image effect.
   *
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The image style.
   *
   * @return bool
   *   Return true/false if the responsive image effect is set.
   */
  public function imageStyleHasResponsiveEffect(ImageStyleInterface $image_style) {
    foreach ($image_style->getEffects() as $effect) {
      if ($effect instanceof ResponsiveImageEffect) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
