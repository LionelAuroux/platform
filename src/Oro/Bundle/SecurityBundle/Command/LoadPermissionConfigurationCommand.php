<?php

namespace Oro\Bundle\SecurityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;

class LoadPermissionConfigurationCommand extends ContainerAwareCommand
{
    const NAME = 'oro:permission:configuration:load';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Load permissions configuration from configuration files to the database')
            ->addOption(
                'permissions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the permissions that should be loaded'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedPermissions = $input->getOption('permissions') ?: null;

        /** @var PermissionConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()
            ->get('oro_security.configuration.provider.permission_configuration');
        $permissionConfiguration = $configurationProvider->getPermissionConfiguration(
            $usedPermissions
        );

        $permissionsConfiguration = $permissionConfiguration[PermissionConfigurationProvider::ROOT_NODE_NAME];
        $this->loadPermissions($output, $permissionsConfiguration);
    }

    /**
     * @param OutputInterface $output
     * @param array $configuration
     */
    protected function loadPermissions(OutputInterface $output, array $configuration)
    {
        $permissions = $this->getContainer()
            ->get('oro_security.configuration.builder.permission_configuration')
            ->buildPermissions($configuration);

        if ($permissions) {
            $output->writeln('Loading permissions...');

            /** @var EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine')
                ->getManagerForClass('OroSecurityBundle:Permission');
            /** @var EntityRepository $permissionRepository */
            $permissionRepository = $entityManager->getRepository('OroSecurityBundle:Permission');

            foreach ($permissions as $permission) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $permission->getName()));

                /** @var Permission $existingPermission */
                $existingPermission = $permissionRepository->findOneBy(['name' => $permission->getName()]);

                // permission in DB should be overridden if permission with such name already exists
                if ($existingPermission) {
                    $existingPermission->import($permission);
                } else {
                    $entityManager->persist($permission);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No permissions found.');
        }
    }
}
