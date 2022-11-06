<?php

/**
 * @file
 * Contains Drupal\custom_rest\Plugin\rest\resource\custom_rest.
 */

namespace Drupal\spia_rest\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\image\Entity\ImageStyle;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "login",
 *   label = @Translation("User login"),
 *    serialization_class = "",
 *   uri_paths = {
 *     "create" = "/rest/login"
 *   }
 * )
 */
class Login extends ResourceBase
{
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   *   A current user instance.
   */
  public function __construct(
    array                 $configuration,
                          $plugin_id,
                          $plugin_definition,
    array                 $serializer_formats,
    LoggerInterface       $logger,
    AccountProxyInterface $current_user)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
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
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   */
  public function post(Request $request)
  {
    $content = $request->getContent();
    $decoded = json_decode($content, true);
    $email = $decoded['email'];
    $password = $decoded['password'];

    try {
      if (!empty($email) & !empty($password)) {
        $user_array = \Drupal::entityTypeManager()
          ->getStorage('user')
          ->loadByProperties([
            'mail' => $email,
          ]);
        if (!empty($user_array)) {
          $user = reset($user_array);
          $password_hasher = \Drupal::service('password');
          if ($password_hasher->check($password, $user->getPassword())) {
            $token = bin2hex(random_bytes(32));
            $user->set('field_access_token', $token);
            try {
              $user->save();
              $fid = $user->get('field_picture')->getValue()[0]['target_id'];
              $file = \Drupal\file\Entity\File::load($fid);
              $data = [
                'access_token' => $token,
                'first_name' => $user->get('field_first_name')->getValue()[0]['value'],
                'last_name' => $user->get('field_last_name')->getValue()[0]['value'],
                'email' => $user->getEmail(),
                'picture' => ImageStyle::load('thumbnail')->buildUrl($file->getFileUri())
              ];
              return new ModifiedResourceResponse([
                "status" => 200,
                "user_data" => $data
              ]);
            } catch (\Exception) {
              throw new \Exception('User Error saving token');
            }
          } else {
            throw new \Exception('Incorrect password');
          }
        } else {
          throw new \Exception('User not exist or is blocked');
        }
      } else {
        throw new \Exception('Password or email not set');
      }
    } catch (\Exception $e) {
      return new ModifiedResourceResponse([
        "status" => 400,
        "message" => $e->getMessage()
      ]);
    }
  }

}
