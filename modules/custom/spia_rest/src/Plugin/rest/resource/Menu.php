<?php

namespace Drupal\spia_rest\Plugin\rest\resource;


use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use \Drupal\spia_rest\Utils\Auth;

/**
 * Provides a resource for testing the Rest API.
 *
 * @RestResource(
 *   id = "menu",
 *   label = @Translation("Get Menu"),
 *   uri_paths = {
 *     "canonical" = "/rest/page/{nid}/menu"
 *   }
 * )
 */
class Menu extends ResourceBase
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
  public function get($nid)
  {
    $data = $this->currentRequest->query->all();
    $email = $data['email'];
    $auth_token =  $data['access_token'];
    $auth = new Auth();
    if ($auth->authorize($email, $auth_token)) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      return new ModifiedResourceResponse($this->getMenu($node));
    } else {
      return new ModifiedResourceResponse([
        'status' => 404,
        'message' => 'user unauthorized, Please login'
      ]);
    }
  }

  private function getMenu($node)
  {

    $data = [];
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $base_url = \Drupal::request()->getBaseUrl();
    $i = 0;
    foreach ($node->get('field_widgets_ph')->getValue() as $content) {
      try {
        $paragraph = Paragraph::load($content['target_id']);
      } catch (\Exception $ex) {
        throw new \Exception('Unable to load entity ' . $content->target_id);
      }
      $bundle = $paragraph->bundle();
      $label = $paragraph->get('field_label')->getValue();
      $label_value = !empty($label) ? $label[0]['value'] : 'DATA-'.$paragraph->id();
      if (!isset($data[$bundle])) {
        $data[$bundle] = [
          'label' => ucfirst($bundle),
          'dataset' => []
        ];
      }

      $data[$bundle]['dataset']['widget-'.$paragraph->id()] = [
        'label' => $label_value,
        'widgets' => []
      ];



      $types = '';
      switch ($bundle) {
        case 'chart':
          $types = $paragraph->get('field_chart_type_ref')->getValue();
          break;
        case 'text':
          $types = $paragraph->get('field_text_type_ref')->getValue();
          break;
      }
      if(!empty($types)) {
        foreach ($types as $type) {
          $tid = $type['target_id'];
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
          $class = $term->get('field_class')->getValue();
          $sub_data['label'] = $term->get('name')->value;
          $sub_data['class'] = $class[0]['value'];
          $sub_data['url'] = $host.$base_url.'/rest/widget/'.$paragraph->id();
          $data[$bundle]['dataset']['widget-'.$paragraph->id()]['widgets'][] = $sub_data;
        }
      }else {
        unset($data[$bundle]['dataset']['widget-'.$paragraph->id()]['widgets']);
        $data[$bundle]['dataset']['widget-'.$paragraph->id()]['class'] = $paragraph->bundle();
        $data[$bundle]['dataset']['widget-'.$paragraph->id()]['url'] = $host.$base_url.'/rest/widget/'.$paragraph->id();
      }

      $i++;
    }

    return $data;
  }

}
