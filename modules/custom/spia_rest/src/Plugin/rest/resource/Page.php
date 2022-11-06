<?php

namespace Drupal\spia_rest\Plugin\rest\resource;


use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a resource for testing the Rest API.
 *
 * @RestResource(
 *   id = "page",
 *   label = @Translation("Get Page"),
 *   uri_paths = {
 *     "canonical" = "/rest/page/{nid}"
 *   }
 * )
 */
class Page extends ResourceBase {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($nid) {
    $build = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );
    $data = $this->currentRequest->query->all();

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if(isset($data['menu'])) {
      return (new ResourceResponse($this->getMenu($node)))->addCacheableDependency($build);
    }
    if(isset($data['test'])) {
      $jayParsedAry = [
        "primo_item" => [
          "label" => "1 Padre",
          "type" => "one_column",
          "rest_url" => "http://spianopolese.it/rest/alive",
          "id" => "1",
          "children" => [
            [
              "label" => "2 Livello 1 Figlio",
              "type" => "one_column",
              "rest_url" => "http://spianopolese.it/rest/alive",
              "id" => "3",
              "children" => [
                [
                  "label" => "3 Livello 1 Figlio",
                  "type" => "one_column",
                  "rest_url" => "http://spianopolese.it/rest/alive",
                  "id" => "5",
                  "children" => [
                    [
                      "label" => "4 Livello 1 Figlio",
                      "type" => "one_column",
                      "rest_url" => "http://spianopolese.it/rest/alive",
                      "id" => "7",
                      "children" => null
                    ]
                  ]
                ],

                [
                  "label" => "3 Livello 2 Figlio",
                  "type" => "one_column",
                  "rest_url" => "http://spianopolese.it/rest/alive",
                  "id" => "6",
                  "children" => [
                    [
                      "label" => "4 Livello 1 Figlio",
                      "type" => "one_column",
                      "rest_url" => "http://spianopolese.it/rest/alive",
                      "id" => "8",
                      "children" => null
                    ],
                    [
                      "label" => "4 Livello 2 Figlio",
                      "type" => "one_column",
                      "rest_url" => "http://spianopolese.it/rest/alive",
                      "id" => "9",
                      "children" => null
                    ]
                  ]
                ]
              ]
            ],
            [
              "label" => "2 Livello 2Figlio",
              "type" => "one_column",
              "rest_url" => "http://spianopolese.it/rest/alive",
              "id" => "4",
              "children" => null
            ]
          ]
        ],
        "secondo_item" => [
          "label" => "2 Padre",
          "type" => "one_column",
          "rest_url" => "http://spianopolese.it/rest/alive",
          "id" => "2",
          "children" => null
        ]
      ];
      return (new ResourceResponse($jayParsedAry))->addCacheableDependency($build);
    }

    return (new ResourceResponse($this->getPage($node)))->addCacheableDependency($build);
  }

  private function getMenu($node) {

    return $this->getWidgetsMenu($node->get('field_widgets_ph')->getValue());
  }

  private function getPage($node) {

    $filtered_body = check_markup($node->get('body')->value, 'filtered_html');

    $body = Html::transformRootRelativeUrlsToAbsolute($filtered_body, \Drupal::request()->getSchemeAndHttpHost());
    $nid = $node->id();

    $data = [
      'nid'   => $nid,
      'label' => $node->get('title')->value,
      'body'  => $body,
      'menu_enabled' => $node->get('field_menu_enabled')->value,
      'widgets' => $this->getWidgets($node->get('field_widgets_ph')->getValue())
    ];
    return $data;
  }

  private function getWidgetsMenu($contents):array{
    $data = [];

    foreach($contents as $content) {
      try {
        $paragraph = Paragraph::load($content['target_id']);
      }
      catch (\Exception $ex) {
        throw new \Exception('Unable to load entity '.$content->target_id);
      }
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $base_url = \Drupal::request()->getBaseUrl();
      $data['item_'.$content['target_id']] = [
        'label'    => $paragraph->get('field_title')->value,
        'type' => $paragraph->bundle(),
        'id' => $content['target_id'],
        'rest_url' => $host.$base_url.'/rest/widget/'.$content['target_id'],
        'children' => NULL
      ];
    }

    return $data;
  }
  private function getWidgets($contents):array{
    $data = [];

    foreach($contents as $content) {
      try {
        $paragraph = Paragraph::load($content['target_id']);
      }
      catch (\Exception $ex) {
        throw new \Exception('Unable to load entity '.$content->target_id);
      }


      $filtered_body = check_markup($paragraph->get('field_body')->value, 'filtered_html');
      $body = Html::transformRootRelativeUrlsToAbsolute($filtered_body, \Drupal::request()->getSchemeAndHttpHost());

      $data[] = [
        'label'    => $paragraph->get('field_title')->value,
        'type' => $paragraph->bundle(),
        'id' => $content['target_id'],
        'is_in_menu'  => $paragraph->get('field_menu')->getValue()[0]['value'],
        'body' => $body,
        'widgets' => $this->getSubWidgets($paragraph->get('field_widgets_ph')->getValue())
      ];
    }

    return $data;
  }
  private function getSubWidgets($contents):array{
    $data = [];

    foreach($contents as $content) {
      try {
        $paragraph = Paragraph::load($content['target_id']);
      }
      catch (\Exception $ex) {
        throw new \Exception('Unable to load entity '.$content->target_id);
      }


      $filtered_body = check_markup($paragraph->get('field_body')->value, 'filtered_html');
      $body = Html::transformRootRelativeUrlsToAbsolute($filtered_body, \Drupal::request()->getSchemeAndHttpHost());

      $data[] = [
        'label'    => $paragraph->get('field_title')->value,
        'type' => $paragraph->bundle(),
        'id' => $content['target_id'],
        'is_in_menu'  => $paragraph->get('field_menu')->getValue()[0]['value'],
        'body' => $body
      ];
    }

    return $data;
  }
}
