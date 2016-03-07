<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Pumukit\SchemaBundle\PumukitSchemaBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle($this),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Sylius\Bundle\ResourceBundle\SyliusResourceBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new SunCat\MobileDetectBundle\MobileDetectBundle(),
            new Pumukit\EncoderBundle\PumukitEncoderBundle(),
            new Pumukit\InspectionBundle\PumukitInspectionBundle(),
            new Pumukit\NewAdminBundle\PumukitNewAdminBundle(),
            new Pumukit\LiveBundle\PumukitLiveBundle(),
            new Pumukit\WorkflowBundle\PumukitWorkflowBundle(),
            new Pumukit\WizardBundle\PumukitWizardBundle(),
            new Pumukit\WebTVBundle\PumukitWebTVBundle(),
            new Pumukit\StatsBundle\PumukitStatsBundle(),
            new Pumukit\BasePlayerBundle\PumukitBasePlayerBundle(),
            new Pumukit\JWPlayerBundle\PumukitJWPlayerBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Pumukit\InstallBundleBundle\PumukitInstallBundleBundle();
            $bundles[] = new Pumukit\ExampleDataBundle\PumukitExampleDataBundle();
            $bundles[] = new Vipx\BotDetectBundle\VipxBotDetectBundle();

        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
