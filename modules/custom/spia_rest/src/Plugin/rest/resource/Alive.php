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

/**
 * Provides a resource for testing the Rest API.
 *
 * @RestResource(
 *   id = "alive",
 *   label = @Translation("Get Status"),
 *   uri_paths = {
 *     "canonical" = "/rest/alive"
 *   }
 * )
 */
class Alive extends ResourceBase {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
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
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns details of a course node.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($nid = NULL) {
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


    return new ResourceResponse($jayParsedAry);

  }
}
