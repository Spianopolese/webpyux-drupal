<?php

namespace Drupal\spia_rest\Plugin\rest\resource;


use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\ModifiedResourceResponse;
use \Drupal\spia_rest\Utils\Auth;

/**
 * Provides a resource for testing the Rest API.
 *
 * @RestResource(
 *   id = "widget",
 *   label = @Translation("Get Widget"),
 *   uri_paths = {
 *     "canonical" = "/rest/widget/{target_id}"
 *   }
 * )
 */
class Widget extends ResourceBase
{

  /**
   *  A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user instance.
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current request
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('example_rest'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns details of a course node.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function get($target_id)
  {
    $data = $this->currentRequest->query->all();
    $email = $data['email'];
    $auth_token =  $data['access_token'];
    $auth = new Auth();
    if ($auth->authorize($email, $auth_token)) {
      return new ModifiedResourceResponse($this->getWidget($target_id));
    } else {
      return new ModifiedResourceResponse([
        'status' => 404,
        'message' => 'user unauthorized, Please login'
      ]);
    }
  }


  private function getWidget($target_id): array
  {


    try {
      $paragraph = Paragraph::load($target_id);
    } catch (\Exception $ex) {
      throw new \Exception('Unable to load entity ' . $target_id);
    }

    $bundle = $paragraph->bundle();
    $label = $paragraph->get('field_label')->value;
    $label_value = !(is_null($label)) ? $label : 'DATA-'.$paragraph->id();

    switch ($bundle) {
      case 'text':

        $filtered_body = check_markup($paragraph->get('field_body')->value, 'filtered_html');
        $body = Html::transformRootRelativeUrlsToAbsolute($filtered_body, \Drupal::request()->getSchemeAndHttpHost());
        $tid = $paragraph->get('field_text_type_ref')->getValue()[0]['target_id'];
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
        $data = [
          'label' => $label_value,
          'list_view_dataset' => $label_value,
          'list_view_label' => $term->get('name')->value,
          'class' => $paragraph->bundle(),
          'body' => $body,
          'size' => ''
        ];
        $data['size'] = mb_strlen(serialize($data), '8bit');
        return $data;

      case 'chart':

        $json = preg_replace('!\\r?\\n!', "", $paragraph->get('field_data')->getValue()[0]['value']);
        $tid = $paragraph->get('field_chart_type_ref')->getValue()[0]['target_id'];
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
        $class = $term->get('field_class')->getValue();
        $data = [
          'label' => $label_value,
          'list_view_dataset' => $label_value,
          'list_view_label' => $term->get('name')->value,
          'class' => $paragraph->bundle(),
          'type' => $class[0]['value'],
          'data' => json_decode($json,true),
          'size' => ''
        ];
        $data['size'] = mb_strlen(serialize($data), '8bit');
        return $data;

      case 'websocket':

        $data = [
          'label' => $label_value,
          'list_view_dataset' => $label_value,
          'list_view_label' => '',
          'class' => $paragraph->bundle(),
          'data' => [
            'url' => $paragraph->get('field_websocket_url')->getValue()[0]['value'],
            'topic' => $paragraph->get('field_topic')->getValue()[0]['value'],
          ],
          'size' => ''
        ];
        $data['size'] = mb_strlen(serialize($data), '8bit');

        return $data;
    }
    return [];
  }
  private function barCharData($elements, $title) {
    $data = [
      'labels' => [],
      'datasets' => []
    ];
    $bar = [
      'label' => $title,
      'data' => [],
      'backgroundColor' => [],
      'borderColor' => []
    ];
    foreach($elements as $element) {
      try {
        $paragraph = Paragraph::load($element['target_id']);
      }
      catch (\Exception $ex) {
        throw new \Exception('Unable to load entity '.$element->target_id);
      }
      $color = $paragraph->get('field_color')->getValue()[0]['color'];
      $opacity = $paragraph->get('field_color')->getValue()[0]['opacity'];

      $data['labels'][] = $paragraph->get('field_title')->value;
      $bar['data'][] = intval($paragraph->get('field_value')->value);
      $bar['backgroundColor'][] = $this->hex2rgba($color, $opacity);
      $bar['borderColor'][] = $this->hex2rgba($color);
    }
    $data['datasets'][] = $bar;

    return $data;
  }
  private function hex2rgba($color, $opacity = false) {
    $default = 'rgb(0,0,0)';

    if (empty($color))
      return $default;

    if ($color[0] == '#')
      $color = substr($color, 1);

    if (strlen($color) == 6)
      $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);

    elseif (strlen($color) == 3)
      $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);

    else
      return $default;

    $rgb = array_map('hexdec', $hex);

    if ($opacity) {
      if (abs($opacity) > 1)
        $opacity = 1.0;

      $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    } else {
      $output = 'rgb(' . implode(",", $rgb) . ')';
    }
    return $output;
  }
}
