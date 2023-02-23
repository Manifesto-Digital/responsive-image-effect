<?php

namespace Drupal\responsive_image_effect\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\responsive_image_effect\Service\ResponsiveImageEffectService;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class ResponsiveImageEffectCommands extends DrushCommands {

  /**
   * Responsive Image Effect service.
   *
   * @var \Drupal\responsive_image_effect\Service\ResponsiveImageEffectService
   */
  protected $responsiveImageEffectService;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ResponsiveImageEffectService $responsiveImageEffectService, EntityTypeManagerInterface $entityTypeManager) {
    $this->responsiveImageEffectService = $responsiveImageEffectService;
    $this->entityTypeManager = $entityTypeManager;

    parent::__construct();
  }

  /**
   * Generate an image responsive url for fun.
   *
   * @param int $media_id
   *   Argument provided to the drush command.
   * @param int $width
   *   The width to make the responsive image.
   * @param int $height
   *   (optional) The width to make the responsive image.
   * @param array $options
   *   (optional) Options array.
   *
   * @command rie:genRespImg
   * @aliases rig
   * @arg media_id A media entity id
   * @arg width Width of the responsive image in pixels.
   * @arg height height of the responsive image in pixels.
   * @options --crop boolean to crop to the dimensions using focal point. You can also provide a value here to calculate the crop ratio if you do not specify a height.
   * @options --style string to specify the image style to use (defaults to 'responsive').
   * @usage rie:genRespImg media_id width --crop
   *   Displays a URL for the image
   */
  public function generateImageUrl($media_id, $width, $height = 0, array $options = [
    'crop' => 1,
    'style' => 'responsive',
  ]) {
    $width = (int) $width;
    $height = (int) $height;
    $image_style_name = $options['style'];

    $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    $uri = $file->getFileUri();

    $params = ['w' => $width];

    if ($height) {
      $params['h'] = $height;
      $params['c'] = !empty($options['crop']);
    }
    elseif (!empty($options['crop'])) {
      $ratio = (double) $options['crop'];
      $params = $this->responsiveImageEffectService->crop($width, $ratio);
    }

    $src = $this->responsiveImageEffectService->responsiveImageUrl($uri, $params, $image_style_name);
    $this->output()->writeln($src);
  }

}
