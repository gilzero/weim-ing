<?php

namespace Drupal\group;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Plugin\Discovery\AttributeDiscoveryWithAnnotations;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters existing services for the Group module.
 */
class GroupServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Automatically create missing handler services for group relations and
    // add important attributes to those already declared.
    $modules = $container->getParameter('container.modules');
    $discovery = new AttributeDiscoveryWithAnnotations(
      'Plugin/Group/Relation',
      $container->get('container.namespaces'),
      GroupRelationType::class,
      'Drupal\group\Annotation\GroupRelationType',
      []
    );

    $handlers = [
      'access_control' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyAccessControl',
      'entity_reference' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyEntityReference',
      'operation_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyOperationProvider',
      'permission_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyPermissionProvider',
      'post_install' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyPostInstall',
      'ui_text_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyUiTextProvider',
    ];

    $services = [];
    foreach ($discovery->getDefinitions() as $group_relation_type_id => $group_relation_type) {
      assert($group_relation_type instanceof GroupRelationTypeInterface);
      // Skip plugins that whose provider is not installed.
      if (!isset($modules[$group_relation_type->getProvider()])) {
        continue;
      }

      foreach ($handlers as $handler => $handler_class) {
        $service_name = "group.relation_handler.$handler.$group_relation_type_id";
        $definition = $container->has($service_name)
          ? $container->getDefinition($service_name)
          // Define the service and pass it the default one to decorate.
          : new Definition($handler_class, [new Reference("group.relation_handler.$handler")]);

        // All handlers cannot be shared services.
        $definition->setPublic(TRUE);
        $definition->setShared(FALSE);
        $container->setDefinition($service_name, $definition);

        $services[$service_name] = new Reference($service_name);
      }
    }

    $manager = $container->getDefinition('group_relation_type.manager');
    $manager->addArgument(ServiceLocatorTagPass::register($container, $services));
  }

}
