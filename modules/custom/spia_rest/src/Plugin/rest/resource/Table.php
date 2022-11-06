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
use Drupal\mysql\Driver\Database\mysql\Connection;
/**
 * Provides a resource for testing the Rest API.
 *
 * @RestResource(
 *   id = "table",
 *   label = @Translation("Get Table"),
 *   uri_paths = {
 *     "canonical" = "/rest/table"
 *   }
 * )
 */
class Table extends ResourceBase {

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
   * @var Drupal\mysql\Driver\Database\mysql\Connection
   */
  protected $database;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, Request $current_request, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
    $this->database = $database;
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
      $container->get('database')
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
    $build = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );

    $result = 0;
    $data = $this->currentRequest->query->all();
    $response = '';
    $select = "SELECT COUNT(*) FROM {movies}";
    $query = $this->database->query($select);
    $count_query = $query->fetchAll();

    $count = ['count'=>reset($count_query[0])];
    if(isset($data['count'])){

      return (new ResourceResponse($count))->addCacheableDependency($build);
    }
    if(isset($data['page']) && isset($data['size'])){
      $start = (intval($data['page']) - 1 ) * intval($data['size']);

      $query = $this->database->select('movies', 'x')
        ->fields('x', array('Id','Titolo', 'Anno' , 'Minuti' , 'Genere'))
        ->range($start, intval($data['size']));
      if(isset($data['sort'])) {
        $query->orderBy($data['sort'][0]['field'], strtoupper($data['sort'][0]['dir']));
      }
      $datas = $query->execute();
      $movies = $datas->fetchAll();
      $json = [];
      foreach ($movies as $movie) {
        $json[] = (array)$movie;
      }
      $data = [
        "last_page" => intval(reset($count_query[0]) /$data['size']),
        "data" => $json
      ];
      return (new ResourceResponse((
        $data
      )))->addCacheableDependency($build);
    }


    return (new ResourceResponse($response))->addCacheableDependency($build);
  }

  private function getCount() {

  }
}
