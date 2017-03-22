#!/usr/bin/env php 
<?php

set_time_limit(0);

$loader = require __DIR__.'/../../../../../app/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\DepositorBundle\Services\NotificationService;

class addRoleAddOwnerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:add:role:addroleowner')
            ->setDescription('Update the users and multimedia objects with depositors.')
            ->setHelp(<<<'EOT'

Command to add the role ROLE_ADD_OWNER to users with scope global or personal.

To execute this command:

php src/Pumukit/SchemaBundle/Resources/doc/addRoleAddOwnerCommand.php pumukit:add:role:addroleowner

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $userRepo = $dm->getRepository('PumukitSchemaBundle:User');
        $allUsers = $userRepo->findAll();
        foreach ($allUsers as $user) {
            if (($user->hasRole('ROLE_SCOPE_GLOBAL') || $user->hasRole('ROLE_SCOPE_PERSONAL')) && !$user->hasRole('ROLE_ADD_OWNER')) {
                $user->addRole('ROLE_ADD_OWNER');
                $dm->persist($user);
                $output->writeln('<info>Add ROLE_ADD_OWNER to user "'.$user->getUsername().'".</info>');
            }
        }
        $dm->flush();
        $output->writeln('<info>Finish execution.</info>');
    }
}

$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

if ($debug) {
    Debug::enable();
}
$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->add(new addRoleAddOwnerCommand());
$application->run($input);
                             