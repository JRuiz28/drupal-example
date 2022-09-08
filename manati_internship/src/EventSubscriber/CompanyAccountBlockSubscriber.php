<?php

namespace Drupal\manati_internship\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Manati Internship event subscriber.
 */
class CompanyAccountBlockSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(RouteMatchInterface $route_match, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    // Get ID current user.
    $uid = $this->currentUser->id();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    if ($user && $user->hasRole('company')) {
      $today = new DrupalDateTime('today', DateTimeItemInterface::STORAGE_TIMEZONE);
      $snooze_date_end = $user->field_snooze_date_end->value;
      $company_blocked = !$user->field_call_end_date_interships->isEmpty() || !$user->field_end_date_interships->isEmpty();

      if ($company_blocked && (!$snooze_date_end || $snooze_date_end < $today->format(DateTimeItemInterface::DATE_STORAGE_FORMAT))) {
        // Has internship blocked for call end date.
        if (!$user->field_call_end_date_interships->isEmpty()) {
          // Get routes allowed.
          $allowed_routes = [
            'view.users.interships_end_call_date_page',
            'manati_internship.internship_call_end_date_feedback',
            'user.logout',
          ];

          // Get the current path to verify
          // that it is not in the process of unlocking.
          $current_route = $this->routeMatch->getRouteName();

          if (!in_array($current_route, $allowed_routes)) {
            $url = Url::fromRoute('view.users.interships_end_call_date_page');
            $response = new RedirectResponse($url->toString(), 302);
            $event->setResponse($response);
            $this->messenger->addWarning('Para desbloquear su cuenta debe hacer click en el nombre de la pasantía y llenar el formulario correspondiente.');
          }
        }
        // Has internship blocked for internship end date.
        elseif (!$user->field_end_date_interships->isEmpty()) {
          // Get routes allowed.
          $allowed_routes = [
            'view.users.interships_end_date_page',
            'manati_internship.internship_end_date_feedback',
            'user.logout',
          ];

          // Get the current path to verify
          // that it is not in the process of unlocking.
          $current_route = $this->routeMatch->getRouteName();

          if (!in_array($current_route, $allowed_routes)) {
            $url = Url::fromRoute('view.users.interships_end_date_page');
            $response = new RedirectResponse($url->toString(), 302);
            $event->setResponse($response);
            $this->messenger->addWarning('Para desbloquear su cuenta debe hacer click en el nombre de la pasantía y llenar el formulario correspondiente.');
          }
        }
      }
      elseif (!$company_blocked && $snooze_date_end) {
        $user->set('field_snooze_date_end', NULL);
        $user->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
