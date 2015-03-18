<?php

namespace RESTEssentials;

class DiscoveryEntity {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    private $entityFolder;
    private $namespace;
    private $dbConfig;

    public function __construct($em, $dbConfig) {
        $this->em = $em;
        $this->entityFolder = getcwd() . DIRECTORY_SEPARATOR . 'entity';
        $this->namespace = 'Entity\\';
        $this->dbConfig = $dbConfig;
    }

    public function prepareFolder() {
        is_dir($this->entityFolder)? : mkdir($this->entityFolder, 0777, true);
        is_dir($this->entityFolder . DIRECTORY_SEPARATOR . 'proxies')? : mkdir($this->entityFolder . DIRECTORY_SEPARATOR . 'proxies', 0777, true);
    }

    private function getDbConfigs() {
        return array(
            'driver' => 'pdo_mysql',
            'host' => $this->dbConfig['host'],
            'port' => $this->dbConfig['port'],
            'user' => $this->dbConfig['user'],
            'password' => $this->dbConfig['password'],
            'dbname' => $this->dbConfig['dbname']
        );
    }

    private function configure() {
        $connectionParams = $this->getDbConfigs();

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($this->entityFolder));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir($this->entityFolder . DIRECTORY_SEPARATOR . 'proxies');
        $config->setProxyNamespace('Proxies');
        $this->em = \Doctrine\ORM\EntityManager::create($connectionParams, $config);
    }

    public function checkEntities() {
        if (!is_dir($this->entityFolder)) {
            $this->prepareFolder();
            $this->configure();
            /*
              // custom datatypes (not mapped for reverse engineering)
              $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('set', 'string');
              $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
             */            

            $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
                    $this->em->getConnection()->getSchemaManager()
            );
            $driver->setNamespace($this->namespace);
            $driver->setBiDirecionalEntities(true);
            $this->em->getConfiguration()->setMetadataDriverImpl($driver);
            $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory($this->em);
            $cmf->setEntityManager($this->em);
            $metadata = $cmf->getAllMetadata();
            $this->EntityGenerator($metadata);
        }

        $autoLoader = new \Zend\Loader\StandardAutoloader(array(
            'namespaces' => array(
                'Entity' => $this->entityFolder . DIRECTORY_SEPARATOR . $this->namespace,
            ),
            'fallback_autoloader' => true,
        ));

        $autoLoader->register();
    }

    private function EntityGenerator($metadata) {
        $generator = new \Doctrine\ORM\Tools\EntityGenerator();        
        $generator->setUpdateEntityIfExists(true);
        $generator->setGenerateStubMethods(true);
        $generator->setGenerateAnnotations(true);
        $generator->generate($metadata, $this->entityFolder);
    }

}
