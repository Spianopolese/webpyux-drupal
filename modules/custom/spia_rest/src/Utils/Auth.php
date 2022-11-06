<?php

namespace Drupal\spia_rest\Utils;


class Auth
{
  public function authorize($email, $auth_token): bool
  {
    try {
      if (!empty($email) && !empty($auth_token)) {
        $user_array = \Drupal::entityTypeManager()
          ->getStorage('user')
          ->loadByProperties([
            'mail' => $email,
          ]);
        if (!empty($user_array)) {
          $user = reset($user_array);
          $user_code = $user->get('field_access_token')->getValue()[0]['value'];
          if ($user_code === $auth_token) {
            return true;
          } else {

            throw new \Exception('Token mismatch');
          }
        } else {
          throw new \Exception('Empty user');
        }
      } else {
        throw new \Exception('Empty params');
      }
    } catch (\Exception $e) {
      return false;
    }
  }
}
